#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Tue Nov 16 14:27:05 2021
Modified June 2025 - January 2026
- Adapted to new database structure
- Added loading of lipid, experiment metadata and cross-references

Path: Python/UI_DB_Update.py
Description: Script to update the NMRLipids database with new entries


@authors: Fabs, Michael Dondrup

"""

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# MODULES
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
import os
import os.path as osp
import re
import traceback
import sys
import glob
import json
import yaml
import pymysql
import argparse
import numpy as np
import numbers
from fairmd.lipids import *
from fairmd.lipids.core import *
import fairmd.lipids as dbl
import fairmd.lipids.core as NMRDict
from fairmd.lipids.molecules import *
from fairmd.lipids.experiment import ExperimentCollection, ExperimentError



# most of paths should be inserted into the DB relative to repo root
def genRpath(apath):
    return osp.relpath(apath, dbl.FMDL_DATA_PATH)

## ICICIC: set paths


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# ARGUMENTS
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=


# Program description
parser = argparse.ArgumentParser(description='NMRLipids Update v2.0')

# Ubication of data
parser.add_argument(
    "-c", "--config", type=str, default="config.json",
    help=''' JSON file with the configuration of the connection to the DB.
    Default: %(default)s ''')

# System properties
parser.add_argument(
    "-s", "--systems", type=str, nargs='+',  # REQUIRED
    help=""" Path of the system(s). """)

# Force even in case of errors and exceptions, now explicit
parser.add_argument(
    "-f", "--force", action='store_true',
    help=''' Force the insertion of entries even in case of errors/exceptions.
    Default: %(default)s ''')   



# Debug mode
parser.add_argument(
    "-d", "--debug", type=int, default=0,
    help=''' Activate the debug mode. Default: %(default)s ''')     

args = parser.parse_args()


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# SQL Queries
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

# Functions to generate SQL queries, used by the functions below

def get_primary_key(conn, table, schema=None) -> str | None:

    '''
    Get the primary key column name for a given table.
    Parameters
    ----------

    conn : pymysql.connections.Connection
        The database connection.
    table : str
        The name of the table.
    schema : str, optional
        The database schema (database name). If None, the current database is used.
    Returns -------
    str or None
        The primary key column name, or None if not found.
    '''

    sql = """
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = %s
          AND TABLE_NAME = %s
          AND COLUMN_KEY = 'PRI'
        LIMIT 1
    """
    if schema is None:
        with conn.cursor() as cur:
            cur.execute("SELECT DATABASE()")
            schema = cur.fetchone()[0]

    with conn.cursor() as cur:
        cur.execute(sql, (schema, table))
        row = cur.fetchone()

    return row[0] if row else None


def UPSERT(conn, table, data) -> int | None:
    """
    Generic MySQL UPSERT using PyMySQL.

    - User does NOT provide primary key
    - Returns primary key value if present
    Parameters
    ----------
    conn : pymysql.connections.Connection
        The database connection.
    table : str
        The name of the table.
    data : dict
        The data to insert or update.
    Returns -------
    int or None
        The primary key value if present, otherwise None.

    """

    pk = get_primary_key(conn, table)

    columns = list(data.keys())
    values = list(data.values())

    placeholders = ", ".join(["%s"] * len(columns))
    col_list = ", ".join(f"`{c}`" for c in columns)

    update_parts = [
        f"`{c}` = new.`{c}`"
        for c in columns
    ]

    # Force LAST_INSERT_ID(pk) so we get pk on UPDATE too
    # only if pk is not part of the columns being updated
    if pk and pk not in columns:
        update_parts.append(
            f"`{pk}` = LAST_INSERT_ID(`{pk}`)"
        )

    update_clause = ", ".join(update_parts)

    sql = f"""
        INSERT INTO `{table}` ({col_list})
        VALUES ({placeholders}) AS new
        ON DUPLICATE KEY UPDATE
            {update_clause}
    """

    with conn.cursor() as cursor:
        cursor.execute("SET SESSION sql_mode='STRICT_ALL_TABLES';")
        # Print query for debugging only if debug mode > 1
        if args.debug > 1: 
            print(f"Executing UPSERT on table {table} with data {data}")
            query = cursor.mogrify(sql, values)
            print("Prepared Query String:")
            print(query)
        cursor.execute(sql, values)

        return cursor.lastrowid if pk else None





def SQL_Select(Table: str, Values: list, Condition: dict = {}) -> str:
    '''
    Generate a SQL query to select values in a table. It compares floats with 1E-5
    tolerance!

    Parameters
    ----------
    Table : str
        Name of the table.
    Values : list
        List of values to select.
    Condition : dict, optional
        Condition(s) for the search.

    Returns
    -------
    str
        The SQL query:
        SELECT Values[0], (...), Values[-1] FROM Table
          WHERE Condition.keys()[0]=Condition.value()[0] AND ...
               Condition.keys()[-1]=Condition.value()[-1]
'''

    Query = (
        ' SELECT ' + ", ".join(map(lambda x: f'`{x}`', Values)) +
        f' FROM `{Table}` '
    )
    # Add a condition to the search
    if Condition:
        comps = []
        for k, v in Condition.items():
            if isinstance(v, numbers.Number) and v != np.ceil(v):
                comp = f'ABS( `{k}` - %s) < 1E-5'
            else:
                comp = f'`{k}` = %s'
            comps.append(comp)
        Query += 'WHERE ' + (" AND ".join(comps))
    return Query


def SQL_Create(Table: str, Values: dict) -> str:
    '''
    Generate a SQL query to insert a new entry in a table.

    Parameters
    ----------
    Table : str
        Name of the table.
    Values : dict
        List of values to insert.
    

    Returns
    -------
    str
        The SQL query:
        INSERT INTO Table ( Values.keys()[0], ..., Values.keys()[-1] ) VALUES
                    ( Values.values()[0], ..., Values.values()[-1] )
           
    '''
    Query = (
        f' INSERT INTO `{Table}` (' +
        ", ".join(map(lambda x: f'`{x}`', Values.keys())) +
        ") VALUES (" + (','.join(["%s"]*len(Values)))  + ')'
    )    
    return Query


def SQL_Update(Table: str, Values: dict, Condition: dict = {}) -> str:
    '''
    Generate a SQL query to update an entry in a table.

    Parameters
    ----------
    Table : str
        Name of the table.
    Values : dict
        List of values to insert.
    Condition : dict, optional
        Condition(s) for the insertion.

    Returns
    -------
    str
        The SQL query:
        UPDATE Table SET Values.keys()[0] = Values.values()[0], ...,
                         Values.keys()[-1] = Values.values()[-1]
          WHERE Condition.keys()[0]=Condition.value()[0] AND ...
               Condition.keys()[-1]=Condition.value()[-1]
    '''
    Query = (
        f' UPDATE `{Table}` SET ' +
        ', '.join(map(lambda x: f'`{x[0]}`="{x[1]}"', Values.items())) + ' '
    )

    # Add a condition to the search
    if Condition:
        Query += (
            'WHERE ' +
            " AND ".join(map(lambda x: f'`{x[0]}`="{x[1]}"', Condition.items()))
        )

    return Query


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# Functions
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

def CheckEntry(Table: str, LipidInformation: dict = {}) -> int:
    '''
    Find an entry in the DB

    Parameters
    ----------
    Table : str
        Name of the table.
    LipidInformation : dict, optional
        Values to check.

    Returns
    -------
    int or None
        ID of the entry in the table. If it does not exists, the value is None
    '''
    ID = None
    # Create a cursor
    with database.cursor() as cursor:
        # Find the ID(s) of the entry matching the condition
        values = tuple(LipidInformation.values())
        if args.debug: print(f"Executing query to check entry in {Table} with conditions {values}")
        # Use mogrify to get the composed query string as bytes
        query = SQL_Select(Table, ["id"], LipidInformation)
        if args.debug: print("Preparing Query: {}".format(query))
        composed_query_str = cursor.mogrify(query, values)
        #print("Composed Query String (Before Execution):")
        #print(composed_query_str)
        try:           
            cursor.execute(query, values)
            ID = cursor.fetchall() # Values should be unique
            if ID:
                assert len(ID) <= 1, \
                "Only one ID should be returned for unique entries " + str(LipidInformation) \
                + " in table " + Table + "\n" \
                + composed_query_str
                return ID[0][0]
                 # extract values from dict
            else:
                return None   
        except pymysql.Error as err:
            print(f"Error: {err}")
            # You can also use it here to log the failed query:
            print("Failed Query String:")
            print(composed_query_str)
            raise err

        finally:
            if cursor:
                cursor.close()
       
    return None

def LinkEntries(Table: str, LipidInformation: dict) -> None:
    '''
    Link two entries in a table

    Parameters
    ----------
    Table : str
        Name of the table.
    LipidInformation : dict
        Values to add.
        Must contain the IDs of the two entries to link in the source tables.

    Returns
    -------
    None: Linker table is not expected to return an ID
    '''
    Query = "INSERT INTO `{}` (".format(Table) + \
            ", ".join(map(lambda x: f'`{x}`', LipidInformation.keys())) + \
            ") VALUES  (\"%d,%d\") "
    # Create a cursor
    with database.cursor() as cursor:
        # Execute the query creating a new entry
        res = cursor.execute(SQL_Create(Table, LipidInformation), list(LipidInformation.values()))

    # Commit the changes
    database.commit()

    # Num rows affected should be 1
    if res != 1:
        RuntimeError("ERROR: record wasn't inserted!")
        
            
    
    #print("A new entry was created in {}: index {}".format(Table, LipidInformation))
    return None

def CreateEntry(Table: str, LipidInformation: dict) -> int:
    '''
    Add an entry into a table

    Parameters
    ----------
    Table : str
        Name of the table.
    LipidInformation : dict, optional
        Values to add.

    Returns
    -------
    int
        ID of the entry in the table. If it does not work, value will be 0.
    '''
    ID = None
    # Create a cursor
    with database.cursor() as cursor:
        # Execute the query creating a new entry
        if args.debug: print(f"Executing query to create entry in {Table} with values {LipidInformation}")
        res = cursor.execute(SQL_Create(Table, LipidInformation), tuple(LipidInformation.values()))
        ID = cursor.lastrowid
    # Commit the changes
    database.commit()
    cursor.close()

    # Num rows affected should be 1
    if res != 1:
        print("ERROR: record wasn't inserted!")
        print(LipidInformation)
        raise RuntimeError("ERROR: record wasn't inserted!")

    # Check if the entry was created
    # Get the ID of the created entry
    # If there is not an ID, raise an error (the table was not created)
    if not ID:
        print("WARNING: Something may have gone wrong with the table {}".format(Table))
        print(LipidInformation)
        raise RuntimeError("ERROR: record wasn't found after insertion!")
    # If an ID is obtained, the entry was created succesfuly
    else:
        if args.debug: print("A new entry was created in {}: index {}".format(Table, ID))
        return ID


# --- Load lipid metadata and insert cross-references ---
def load_lipid_metadata(lipid, database):
    meta = lipid.metadata or {}
    lipid_LipidInfo = meta.get('NMRlipids', {})
    bioschema = meta.get('bioschema_properties', {})
    sameas = meta.get('sameAs', {})

    # Insert lipid into lipids table
    molecule_id = lipid.name
    if not molecule_id:
        raise ValueError(f"Error in metadata, Lipid name cannot be empty")
        
    lipid_data = {
        'molecule': molecule_id,
        'name': lipid_LipidInfo.get('name', '') or molecule_id, 
        'mapping': lipid_LipidInfo.get('mapping', molecule_id),
    }
    lipid_id = UPSERT(database, 'lipids', lipid_data)
    if args.debug: print ("Inserted/Updated lipid {} with ID {}".format(molecule_id, lipid_id))

    # Insert synonyms
    synonyms = bioschema.get('alternateNames', [])
    for synonym in synonyms:
        synonym_data = {
            'lipid_id': lipid_id,
            'synonym': synonym
        }
        UPSERT(database, 'lipids_synonyms', synonym_data)
        if args.debug: print ("Inserted synonym {} for lipid ID {}".format(synonym, lipid_id)) 

    # Insert bioschema properties as properties (optional, can be extended)
    for prop, value in bioschema.items():
        if prop in ['@context', '@type', 'name', 'alternateName', 'description']:   
            continue  # Skip non-property fields
        prop_data = {
            'name': prop,
            'description': '',
            'value': value,
            'unit': '',
            'type': 'string'
        }
        prop_id = UPSERT(database, 'properties', prop_data)
        # Link lipid and property
        LinkEntries('lipid_properties', {'lipid_id': lipid_id, 'property_id': prop_id})
        if args.debug: print ("Linked property {} to lipid ID {}".format(prop, lipid_id))

    # Insert cross-references
    for db_name, ext_id in sameas.items():
        # Insert db into db table if not exists
        db_data = {
            'name': db_name,
            'description': '',
            'url_schema': '',
            'version': ''
        }
        db_id = UPSERT(database, 'db', db_data)
        crossref_data = {
            'db_id': db_id,
            'lipid_id': lipid_id,
            'external_id': ext_id,
            'external_url': ''
        }
        UPSERT(database, 'cross_references', crossref_data)



def check_exp(expobj) -> bool:
    '''
    Check if an experiment is valid to be inserted into the DB.
    Parameters
    ----------    
    :param exp: Experiment path
    :param README: README metadata
    
    Returns
    -------
    :rtype: bool
    :return: True if the experiment is valid, False otherwise
    
    '''
    exp = expobj.exp_id
    README = expobj.metadata or {}
    if args.debug: print(f"Processing experiment at path: {exp}")
    if (not README):
        print(f"WARNING: Empty metadata for path '{exp}' is. Skipping experiment.", file=sys.stderr)
        return False
    section_from_path = os.path.basename(os.path.normpath(exp))
    section_from_readme = README.get("SECTION")
    if section_from_readme:
        if str(section_from_readme) != str(section_from_path):
            print(f"WARNING: Section in README ('{section_from_readme}') does not match section from path ('{section_from_path}') in experiment path '{exp}'. Skipping experiment.", file=sys.stderr)
            return False
    # check if experiment path follows expected structure doi1/doi2/section
    if exp.count('/') != 2:
        print(f"WARNING: Experiment path '{exp}' does not follow expected structure (doi1/doi2/section). Skipping experiment.", file=sys.stderr)
        return False
    # check if section is numeric, skip if not
    if not section_from_path.isdigit():
        print(f"WARNING: Section '{section_from_path}' in experiment path '{exp}' is not numeric. Skipping experiment.", file=sys.stderr)
        return False
    if not README.get("ARTICLE_DOI") and not README.get("DOI"):
        print(f"WARNING: ARTICLE_DOI is missing in README.yaml in experiment path '{exp}'. Skipping experiment.", file=sys.stderr)
        return False
    return True

def load_experiment_composition(database, Exp_ID, expobj, ExpInfo=None) -> None:
    '''
    Load membrane and solution composition for an experiment.
    
    Parameters
    ----------
    expobj : Experiment object
        The experiment object containing composition information.
    ExpInfo : dict, optional
        Additional experiment information.
    Returns
    -------
    None
    '''
    # Load membrane composition
    README = expobj.metadata or {}
    for lipid_name, lipid_data in expobj.metadata.get("MEMBRANE_COMPOSITION", expobj.metadata.get("MOLAR_FRACTIONS", {})).items():
        lipid_id = UPSERT(database, 'lipids', {'molecule': lipid_name})
        if ExpInfo and ExpInfo.get('type') == 'OP':
            # For OP experiments, read OP data from the experiment object
            # Access the `data` attribute separately so we can handle
            # errors raised by the property accessor (e.g. ExperimentError).
            op_data = {}
            try:
                _data = expobj.data
            except ExperimentError as e:
                if args.debug:
                    print(f"Warning reading OP data for lipid {lipid_name}: {e}", file=sys.stderr)
                op_data = {}
            else:
                if isinstance(_data, dict):
                    op_data = _data.get(lipid_name, {})
                else:
                    try:
                        op_data = _data[lipid_name]
                    except (TypeError, KeyError, IndexError, AttributeError) as exc:
                        if not args.force:
                            raise exc
                        if args.debug:
                            print(f"Warning reading OP data for lipid {lipid_name}: {exc}", file=sys.stderr)
                        op_data = {}

        
        
        comp_data = {
            'experiment_id': Exp_ID,
            'lipid_id': lipid_id,
            'mol_fraction': float(lipid_data),
            'data': json.dumps(op_data) if ExpInfo and ExpInfo.get('type') == 'OP' else None,
        }
        UPSERT(database, 'experiments_membrane_composition', comp_data)
        if args.debug: print (" -- Linked lipid {} to experiment {}, {}".format(lipid_name, Exp_ID, lipid_data))
    
    # Load solution composition
    for compound_name, compound_data in (README.get("SOLUTION_COMPOSITION", README.get("ION_CONCENTRATIONS", {})) or {}).items():
        ion_comp_data = {
            'experiment_id': Exp_ID,
            'compound': compound_name,
            'concentration': float(compound_data),
        }
        UPSERT(database, 'experiments_solution_composition', ion_comp_data)
        if args.debug: print ("Linked ion {} to experiment {}, {}".format(compound_name, Exp_ID, compound_data))

def load_experiment_properties(database, id, expobj) -> None:
    '''
    Load properties for an experiment.
    
    Parameters
    ----------
    id : int
        The experiment ID to link properties to.
    data : dict
        The README metadata containing property information.
    Returns
    -------
    None
    '''
    data = expobj.metadata or {}
    # Insert properties from README into the properties table
    for prop, value in data.items():
        if prop in ['ARTICLE_DOI', 'DATA_DOI', 'DOI', 'SECTION', 'MEMBRANE_COMPOSITION', 'MOLAR_FRACTIONS', 'SOLUTION_COMPOSITION', 'ION_CONCENTRATIONS']:   
            continue  # Skip non-property fields
        # Check if value is a complex type (list or dict)
        value_store = value
        if isinstance(value, (list, dict)):
            value_store = json.dumps(value)  # Convert to JSON string
        prop_data = {
            'name': prop,
            'description': '',
            'value': value_store,
            'unit': '',
            'type': 'string' if isinstance(value, str) 
                else 'integer' if isinstance(value, int) 
                else 'float' if isinstance(value, float) 
                else 'dict' if isinstance(value, dict)
                else 'list' if isinstance(value, list) 
                else 'string'
        }
        # Create new property entry for each property
        prop_id = UPSERT(database, 'experiment_property', prop_data)
        # Link experiment and property
        if args.debug: print ("Linking property {}:{} to experiment ID {}".format(prop_id,prop, id))
        LinkEntries('experiments_properties_linker', {'experiment_id': id, 'property_id': prop_id})
        


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# MAIN PROGRAM
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

if __name__ == '__main__':
    if args.debug:
        from dumper import dump
    # List to store failed entries
    FAILS = []

    # Load the configuration of the connection
    config = json.load(open(args.config, "r"))
    database = pymysql.connect(**config)

    # Load the lipid and experiment metadata and cross-references only if no systems specified
    if not args.systems:
        if True:
            if args.debug: 
                print("\nLoading lipid metadata and cross-references...\n")
        # Load lipid metadata and cross-references
            lipids = lipids_set
            for lipid in lipids:
                load_lipid_metadata(lipid, database)

# -- TABLE `experiments`
# Iterate over each experiment for types OP and FF
        if args.debug: 
            print("\nStarting the processing of the experiments...\n")       
        # Iterate over each experiment
        for exp_type in ('OPExperiment','FFExperiment'):
            for exp in ExperimentCollection.load_from_data(exp_type):
                # get metadata
                metadata = exp.metadata or {}
                section_from_path = os.path.basename(exp.exp_id)  
                if not check_exp(exp): continue
            # Load form factor data file (assuming only one .json file per experiment)
                form_factor_data = exp.data if exp_type == 'FFExperiment' else None

                expInfo = {
                            "article_doi": metadata.get("ARTICLE_DOI", metadata.get("DOI", ""))  ,
                            "data_doi": metadata.get("DATA_DOI", ""),
                            "section" : metadata.get("SECTION", section_from_path),
                            "type" : exp_type[:2],  # 'FF' or 'OP'
                            "data": json.dumps(form_factor_data) if form_factor_data else None,
                            "path": exp
                        }
                # Entry in the DB with the LipidInfo of the experiment
                exp_ID = UPSERT(database, 'experiments', expInfo)
                if args.debug: print ("Inserted experiment {} of type {}".format(exp_ID, exp_type[:2]))
                # Now add the membrane composition if available
                load_experiment_composition(database, exp_ID, exp, ExpInfo=expInfo)
                load_experiment_properties(database, exp_ID, exp)

    # -- TABLE `trajectories, `forcefields`, `lipids_forcefields` and others
    systems = initialize_databank()
    Skipped_Systems_FF = []
    Skipped_Systems_AUTHOR = []
    Linked_Experiments_OP = []
    Linked_Experiments_FF = []
    # Iterate over the loaded systems
    if args.debug: 
        print("\nStarting the processing of the systems...\n")   
        if args.systems:
            print("Only the following systems will be processed:")
            print(args.systems)
            print("")


    # Iterate over the loaded systems/simulations
    # We need to process first the forcefields and lipids_forcefields
    # Specify the FMDL_SIMU_PATH from the environment variable
    FMDL_SIMU_PATH = os.getenv('FMDL_SIMU_PATH', dbl.FMDL_SIMU_PATH)

    for _README in systems:
        README = _README.readme
        if args.systems:
            if README["path"] not in args.systems:
                continue
        try:
            # if True:
            if args.debug: 
                print("\nCollecting data from system:")
                print("System path: " + README["path"] + "\n")

            # The location of the files
            PATH_SIMULATION = osp.join(FMDL_SIMU_PATH, README["path"])

            # In the case a field in the README does not exist, set its value to 0
            README["AUTHORS_CONTACT"] = README.get("AUTHORS_CONTACT", README.get("AUTHOR", "Unknown author"))
            README["FF"] = README.get("FF", "Unknown FF")
            for field in [
                    'AUTHORS_CONTACT', 'COMPOSITION', 'CPT', 'DATEOFRUNNING', 
                    'DOI', 'FF', 'FF_DATE', 'FF_SOURCE', 'GRO', 'LOG',
                    'NUMBER_OF_ATOMS', 'PREEQTIME', 'PUBLICATION', 'SOFTWARE',
                    'SOFTWARE_VERSION', 'SYSTEM', 'TEMPERATURE', 'TIMELEFTOUT', 'TOP',
                    'TPR', 'TRAJECTORY_SIZE', 'TRJ', 'TRJLENGTH', 'TYPEOFSYSTEM',
                    'WARNINGS', 'ID']:
                if field not in README:
                    README[field] = None
            if not README["FF"]:
                # Skip this system if the forcefield is not defined
                if args.debug:
                    print("WARNING: The forcefield is not defined in the README file. ")
                    print("Skipping system: " + README["path"] + "\n")
                Skipped_Systems_FF.append(README["path"])
                continue
            if not README["AUTHORS_CONTACT"]:
                # Skip this system if the forcefield is not defined
                if args.debug: 
                    print("WARNING: The AUTHOR is not defined in the README file. ")
                    print("Skipping system: " + README["path"] + "\n")
                Skipped_Systems_AUTHOR.append(README["path"])
                continue


    # -- TABLE `forcefields`
            # Collect the LipidInformation about the forcefield
            assert "FF" in README and README["FF"]
           
            FFInfo = {
                "name":   README["FF"],
                "date":   README["FF_DATE"] or "Unknown",
                "source": README["FF_SOURCE"] or "Unknown"
                }

            # Entry in the DB with the LipidInfo of the FF
            FF_ID = UPSERT(database, 'forcefields', FFInfo)

    # -- TABLE `lipids_forcefields`
            # Empty dictionaries for the LipidInfo of the lipids
            Lipids = {}
            Lipids_ID = {}
            Lipid_Ranking = {}
            Lipid_Quality = {}
            # Find the lipids in the composition
            for key in README["COMPOSITION"]:
                if key in NMRDict.lipids_set:
                    # Save the quality of the lipid
                    Store = True

                    # Collect the LipidInfo of the lipids
                    LipidInfo = {
                        "molecule":      key,
                        "name":          README["COMPOSITION"][key]["NAME"],
                        "mapping":       README["COMPOSITION"][key]["MAPPING"]
                        }

                    # The entry should already exist in the lipids table
                    # (loaded at the beginning of the script)
                    Lip_ID = CheckEntry('lipids', {"molecule": key})
                    if not Lip_ID:
                        print("WARNING: Lipid {} not found in the DB. Adding it.".format(key))
                        # If it does not exist, create it
                        Lip_ID = UPSERT(database, 'lipids', LipidInfo)
                    # Link the lipid with the forcefield
                    LinkEntries('lipids_forcefields',
                                {"lipid_id": Lip_ID,
                                 "forcefield_id": FF_ID,
                                 "mapping": README["COMPOSITION"][key]["MAPPING"]
                                 })
                    # Store LipidInformation for further steps
                    Lipids[key] = README["COMPOSITION"][key]["COUNT"]
                    Lipids_ID[key] = Lip_ID

   

    # -- TABLE `ions`
            # Empty dictionary for the LipidInfo of the ions
            Ions = {}
            # Find the ions in the composition
            for key in README["COMPOSITION"]:
                if key in NMRDict.solubles_set and key != "SOL": 
                    # Collect the LipidInfo of the ions
                    LipidInfo = {
                        "forcefield_id": FF_ID,
                        "molecule":      key,
                        "name":          README["COMPOSITION"][key]["NAME"],
                        "mapping":       README["COMPOSITION"][key]["MAPPING"]
                        }

                    # Entry in the DB with the LipidInfo of the ion
                    Ion_ID = UPSERT(database, 'ions', LipidInfo)

                    # Store LipidInformation for further steps: Ions[name]=[ID,number]
                    Ions[key] = [Ion_ID, README["COMPOSITION"][key]["COUNT"]]

    
          
   
    # -- TABLE `membranes`
            # Find the proportion of each lipid in the leaflets
            Names = [[], []]
            Number = [[], []]

            for lipid in Lipids:
                if args.debug:
                    print("Processing lipid in membrane:", lipid, Lipids[lipid])
                if len(Lipids[lipid]) != 2:
                    raise RuntimeError("ERROR: Lipid COUNT fields must be a list of two values " +
                                       "for leaflet 1 and leaflet 2 respectively. " +
                                       "Check the COMPOSITION field in the README file. " +
                                       PATH_SIMULATION)    
                if Lipids[lipid][0]:
                    Names[0].append(lipid)
                    Number[0].append(str(Lipids[lipid][0]))
                if Lipids[lipid][1]:
                    Names[1].append(lipid)
                    Number[1].append(str(Lipids[lipid][1]))

            

            Names = [':'.join(Names[0]), ':'.join(Names[1])]
            Number = [':'.join(Number[0]), ':'.join(Number[1])]

            # Collect the LipidInformation about the membrane
            LipidInfo = {
                "forcefield_id":   FF_ID,
                "lipid_names_l1":  Names[0],
                "lipid_names_l2":  Names[1],
                "lipid_number_l1": Number[0],
                "lipid_number_l2": Number[1],
                "geometry":        README["TYPEOFSYSTEM"]
                }

            # Entry in the DB with the LipidInfo of the membrane
            Mem_ID = UPSERT(database, 'membranes', LipidInfo)

    # -- TABLE `trajectories`
            # Collect the LipidInformation about the simulation
            # Without water you have pure booze!
            if not README.get("COMPOSITION") or not isinstance(README.get("COMPOSITION"), dict):
                raise RuntimeError( 
                "ERROR: COMPOSITION section is mandatory and must be a dictionary of lipids\n" +
                "Check the simulation README file in " +
                PATH_SIMULATION)
            if "SOL" not in README["COMPOSITION"]:
                print("WARNING: Water is missing in the composition. ", file=sys.stderr)
                print("Using IMPLICIT as drop in replacement which is BAD! Check README file in", README["path"],"\n", file=sys.stderr)
                 

            trajectoryInfo = {
                "id":              README["ID"],
                "forcefield_id":   FF_ID,
                "membrane_id":     Mem_ID,
                "git_path":        README["path"],
                "system":          README["SYSTEM"],
                "author":          README["AUTHORS_CONTACT"],
                "date":            README["DATEOFRUNNING"],
                "doi":             README["DOI"],
                "number_of_atoms": README["NUMBER_OF_ATOMS"],
                "preeq_time":      README["PREEQTIME"],
                "publication":     README["PUBLICATION"],
                "software":        README["SOFTWARE"],
                "temperature":     README["TEMPERATURE"],
                "timeleftout":     README["TIMELEFTOUT"],
                "trj_size":        README["TRAJECTORY_SIZE"],
                "trj_length":      README["TRJLENGTH"],
                "water_resname":   README.get("COMPOSITION").get("SOL", {"NAME": "IMPLICIT"} ).get("NAME"),
                }

            # The LipidInformation that defines the trajectory
            Minimal = {
                "id":            README["ID"],
                "forcefield_id": FF_ID,
                "membrane_id":   Mem_ID,
                "git_path":      README["path"],
                "system":        README["SYSTEM"]
                }

            # Entry in the DB with the LipidInfo of the trajectory
            Trj_ID = UPSERT(database, 'trajectories', trajectoryInfo)

    # -- TABLE `trajectories_lipids`
            TrjL_ID = {}
            for lipid in Lipids:
                # Collect the LipidInformation of each lipid in the simulation
                LipidInfo = {
                    "trajectory_id": Trj_ID,
                    "lipid_id":      Lipids_ID[lipid],
                    "lipid_name":    lipid,
                    "leaflet_1":     Lipids[lipid][0],
                    "leaflet_2":     Lipids[lipid][1]
                    }

                # The minimal LipidInformation that identifies the lipid
                Minimal = {
                    "trajectory_id": Trj_ID,
                    "lipid_id":      Lipids_ID[lipid]
                    }

                # Entry in the DB with the LipidInfo of the lipids in the simulation
                TrjL_ID[lipid] = UPSERT(database, 'trajectories_lipids', LipidInfo)

    
    # -- TABLE `trajectories_ions`
            TrjI_ID = {}
            for ion in Ions:
                if args.debug:
                    print("Processing ion:", ion, Ions[ion])
                if len(Ions[ion]) != 2:
                    raise RuntimeError("ERROR: Ion counts must be a list of two values " +
                                       "for leaflet 1 and leaflet 2 respectively. " +
                                       "Check the COMPOSITION field in the README file. " +
                                       PATH_SIMULATION)

                # Collect the LipidInformation of each ion in the simulation
                LipidInfo = {
                    "trajectory_id": Trj_ID,
                    "ion_id":        Ions[ion][0],
                    "ion_name":      ion,
                    "number":        Ions[ion][1]}

                # The minimal LipidInformation that identifies the ion
                Minimal = {
                    "trajectory_id": Trj_ID,
                    "ion_id":        Ions[ion][0]}

                # Entry in the DB with the LipidInfo of the ions in the simulation
                TrjI_ID[ion] = UPSERT(database, 'trajectories_ions', LipidInfo)

   
    # -- TABLE `trajectories_membranes``

            LipidInfo = {
                "trajectory_id": Trj_ID,
                "membrane_id": Mem_ID,
                "name": README["SYSTEM"]}

            _ = UPSERT(database, 'trajectories_membranes', LipidInfo)

    # -- TABLE `trajectories_analysis`
            # Find the bilayer thickness
            try:
                with open(osp.join(PATH_SIMULATION, 'thickness.json')) as FILE:
                    BLT = json.load(FILE)
            except Exception:
                BLT = 0

            # Find the area per lipid
            try:
                with open(osp.join(PATH_SIMULATION, 'apl.json')) as FILE:
                    # Load the file
                    ApL = json.load(FILE)

                    # Transform the dictionary into an array
                    ApL = np.array([[float(key), float(ApL[key])] for key in ApL])

                    # Perform the mean
                    APL = np.mean(ApL[int(len(ApL[:, 0])/2):, 1])
            except Exception as e:
                if args.debug:
                    print("WARNING: Could not compute area per lipid.")
                    print("Exception: {}".format(e))
                if not args.force:
                    raise e;
                APL = 0

            # Form factor quality
            try:
                with open(osp.join(PATH_SIMULATION, 'FormFactorQuality.json')) as FILE:
                    FFQ = json.load(FILE)
            except Exception:
                FFQ = [4242, 0]

            # Read the quality file for the whole system
            try:
                with open(osp.join(PATH_SIMULATION, 'SYSTEM_quality.json')) as FILE:
                    QUALITY_SYSTEM = json.load(FILE)
            except Exception as e:
                if args.debug:
                    print("WARNING: Could not load SYSTEM_quality.json file.")
                    print("Exception: {}".format(e))
                

                QUALITY_SYSTEM = {
                    "total": 0,
                    "headgroup": 0,
                    "tails": 0}
            # Find the form factor experiment path
            FFExp = ''
            if "EXPERIMENT" in README and "FORMFACTOR" in README["EXPERIMENT"] and \
                isinstance(README["EXPERIMENT"]["FORMFACTOR"], list) and \
                README["EXPERIMENT"]["FORMFACTOR"] and \
                 README["EXPERIMENT"]["FORMFACTOR"][0]:
                try:
                    FFExp = genRpath(
                        osp.join(FMDL_EXP_PATH, README["EXPERIMENT"]["FORMFACTOR"][0]))
                except Exception as e:
                    if args.debug:
                        print(f"WARNING: Could not generate path for form factor experiment. {README['EXPERIMENT']['FORMFACTOR']} ")
                        print("Exception: {}".format(e))
                        dump(README)
                    if not args.force:
                        raise e

                    

            # Collect the LipidInformation of the analysis of the trajectory
            LipidInfo = {
                "trajectory_id":          Trj_ID,
                "bilayer_thickness":      BLT,
                "area_per_lipid":         APL,
                "area_per_lipid_file":    genRpath(
                    osp.join(FMDL_SIMU_PATH, README["path"], 'apl.json')),
                "form_factor_file":       genRpath(
                    osp.join(FMDL_SIMU_PATH, README["path"], 'FormFactor.json')),
                "quality_total":          QUALITY_SYSTEM["total"],
                "quality_headgroups":     QUALITY_SYSTEM["headgroup"],
                "quality_tails":          QUALITY_SYSTEM["tails"],
                "form_factor_experiment": FFExp,
                "form_factor_quality":    FFQ[0],
                "form_factor_scaling":    FFQ[1]
                }

            # Entry in the DB with the LipidInfo of the analysis of the simulation
            _ = UPSERT(database, 'trajectories_analysis', LipidInfo)

    # -- TABLE `trajectories_analysis_lipids`
            for lipid in Lipids:
                OPExp = ''
                # Find the order parameters experiment path
                if "EXPERIMENT" in README and "ORDERPARAMETER" in README.get("EXPERIMENT", {}) and \
                   README["EXPERIMENT"]["ORDERPARAMETER"] and \
                   lipid in README["EXPERIMENT"]["ORDERPARAMETER"] and \
                     README["EXPERIMENT"]["ORDERPARAMETER"][lipid]:

                    try:
                        OPExp = genRpath(osp.join(
                            FMDL_EXP_PATH, 'OrderParameters',
                            README["EXPERIMENT"]["ORDERPARAMETER"][lipid][0],
                            lipid + '_OrderParameters.json')
                            )
                    except Exception as e:
                        if args.debug:
                            print("WARNING: Could not generate path for order parameters experiment for lipid {}.".format(lipid))
                            print("Exception: {}".format(e))
                        if not args.force:
                            raise e

                # Collect the LipidInformation of each lipid in the simulation
                LipidInfo = {
                    "trajectory_id":                Trj_ID,
                    "lipid_id":                     Lipids_ID[lipid],
                    
                    "order_parameters_file":        genRpath(
                        osp.join(FMDL_SIMU_PATH, README["path"],
                                 lipid + 'OrderParameters.json')),
                    "order_parameters_experiment":  OPExp,
                    "order_parameters_quality":     genRpath(
                        osp.join(FMDL_SIMU_PATH, README["path"],
                                 lipid + '_OrderParameters_quality.json'))
                    }
                if args.debug:
                    print("Processing trajectory analysis lipid:", lipid, LipidInfo)               

                # Entry in the DB with the LipidInfo of the analysis of the lipid
                # in the simulation
                _ = UPSERT(database, 'trajectories_analysis_lipids', LipidInfo)

   
    # -- TABLE `trajectory_analysis_ions`
            for ion in Ions:
                # Collect the LipidInformation of the ions in the simulation
                LipidInfo = {"trajectory_id": Trj_ID,
                        "ion_id":        Ions[ion][0]}

                # The minimal LipidInformation that identifies the ion in the simulation
                # Minimal = { "trajectory_id": Trj_ID,
                #            "ion_id":        Ions[ ion ][0] }

                # Entry in the DB with the LipidInfo of the analysis of the ion in the
                # simulation
                _ = UPSERT(database, 'trajectories_analysis_ions', LipidInfo)

      
    # ------------------
    # -- TABLE `trajectories_experiments_OP` and `trajectories_experiments_FF`        
    # ------------------
            
   
            if "EXPERIMENT" in README and "ORDERPARAMETER" in README.get("EXPERIMENT", {}) and README["EXPERIMENT"]["ORDERPARAMETER"]:
                    # -- TABLE `trajectories_experiments_OP`
                    # The Order Parameters experiments associated to the simulation

                ExpOP = README["EXPERIMENT"]["ORDERPARAMETER"]
                if args.debug:
                    print("Found ORDERPARAMETER experiments for system: " +
                        README["path"])
                # Iterate over the lipids
                for mol in ExpOP:
                    # Check if there is an experiment associated to the lipid
                    if type(ExpOP[mol]) is list or type(ExpOP[mol]) is dict or len(ExpOP[mol]) > 0:
                        #print("Processing Trajectory {} lipid:{}".format(README["path"], mol))                     
                        for path in ExpOP[mol]:                              
                            if args.debug:
                                print("Linking trajectory {} with experiment {} for lipid {}".format(
                                    Trj_ID, path, mol))
                            Linked_Experiments_OP.append(README["path"] + ":" + mol +" ID:" + str(Trj_ID))
                            exp_id = CheckEntry(
                                        'experiments_OP', {
                                        "path": path})
                            if not exp_id:
                                print("WARNING: Experiment not found in DB: " +
                                        path + " for system: " +
                                        README["path"], file=sys.stderr)  
                                continue                               
                            LipidInfo = {
                                "trajectory_id": Trj_ID,
                                "lipid_id": Lipids_ID[mol],
                                "experiment_id": exp_id,
                            }        
                            _ =  UPSERT(database, 'trajectories_experiments_OP', LipidInfo)
                
            else:
                if args.debug:
                    print("WARNING: No ORDERPARAMETER experiments found for system: " +
                            README["path"], file=sys.stderr)  
                    
    # -- TABLE `trajectories_experiments_FF`
                if "FORMFACTOR" in README.get("EXPERIMENT", {}):
                    # The Form Factor experiments associated to the simulation
                    ExpFF = README["EXPERIMENT"]["FORMFACTOR"]

                    if ExpFF:
                        if type(ExpFF) is str:
                            ExpFF = [ExpFF]

                            for path in ExpFF:

                                for file in os.listdir(osp.join(
                                        PATH_EXPERIMENTS_FF, path)):
                                    exp_id = CheckEntry(
                                                    'experiments_FF', {
                                                      #"article_doi": path,
                                                    "path": genRpath(osp.join(
                                                             PATH_EXPERIMENTS_FF,
                                                             path, file))
                                                    })
                                    if not exp_id:
                                        print("WARNING: Experiment not found in DB: " +
                                                path + " for system: " +
                                                README["path"], file=sys.stderr)  
                                        continue

                                    if file.endswith(".json"):
                                        LipidInfo = {
                                            "trajectory_id": Trj_ID,
                                            "experiment_id": exp_id,
                                                     }

                                        _ = UPSERT(database, 'trajectories_experiments_FF',
                                                    LipidInfo)
                                        if args.debug:
                                            print("Linking trajectory {} with experiment {}".format(
                                                Trj_ID, file)) 
                                        Linked_Experiments_FF.append(README["path"] +" ID:" + str(Trj_ID))

        except Exception as err:
            print ("------------------------------------------------------\n", file=sys.stderr)
            print("Exception loading system:" + README["path"], file=sys.stderr)
            traceback.print_exc()
            print ("------------------------------------------------------\n", file=sys.stderr)
            if not args.force:
                raise err
            FAILS.append(README["path"])
  
####################

    if FAILS:
        print(
            "\nThe following systems failed. Please check the files." +
            "\n" + "\n".join(FAILS)
            )
    if len(Skipped_Systems_FF) > 0:
        print(
            "\nThe following systems were skipped due to missing forcefield information:" +
            "\n" + "\n".join(Skipped_Systems_FF)
            )
    if len(Skipped_Systems_AUTHOR) > 0:
        print(
            "\nThe following systems were skipped due to missing author information:" +
            "\n" + "\n".join(Skipped_Systems_AUTHOR)
            )
    if len(Linked_Experiments_OP) >= 0:
        print(
            len(Linked_Experiments_OP), "ORDERPARAMETER experiments were linked to simulations."
            )
    if len(Linked_Experiments_FF) >= 0:
        print(
            len(Linked_Experiments_FF), "FORMFACTOR experiments were linked to simulations."
            )   
    
####################

    database.close()
