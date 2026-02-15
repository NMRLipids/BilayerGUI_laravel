#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Tue Nov 16 14:27:05 2021
Modified June 2025 - January 2026
- Adapted to new database structure
- Added loading of lipid, experiment metadata and cross-references

Path: Python/GUI_DB_Update.py
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
from importlib import import_module
import DatabankLib as dbl # requires the package to be pre-installed
from DatabankLib import *
from DatabankLib.core import *
from DatabankLib.settings import *



# IMPORTLIB imports just `core` and `databankio` to avoid additional dependecies.
# It DOES NOT require the package to be pre-installed
#sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
#dbl = import_module("../Databank/DatabankLib", "DatabankLib")
NMRDict = import_module("DatabankLib.settings.molecules")
#core = import_module("../Databank/DatabankLib.core", "core")
#sys.path.pop(0)


# most of paths should be inserted into the DB relative to repo root
def genRpath(apath):
    return osp.relpath(apath, dbl.NMLDB_DATA_PATH)

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


def UpdateEntry(Table: str, LipidInformation: dict, Condition: dict):
    '''
    Updates an entry in a table.

    Parameters
    ----------
    Table : str
        Name of the table.
    LipidInformation : dict
        Values to add.
    Condition : dict
        Conditions to select the entry.
    '''

    # Create a cursor
    with database.cursor() as cursor:
        # Execute the query updating an entry
        query = SQL_Update(Table, LipidInformation, Condition)
        composed_query_str = cursor.mogrify(query)
        if args.debug: 
            print("Composed Query String (Before Execution):")
            print(composed_query_str)
        try:
            cursor.execute(query)
            # Commit the changes
            database.commit()
        except pymysql.Error as err:
            print(f"Error: {err}")
            # You can also use it here to log the failed query:
            print("Failed Query String:")
            print(composed_query_str)
            raise err
        finally:
            if cursor:
                cursor.close()  
    if args.debug: print("Entry {} in table {} was updated".format(Condition["id"], Table))
    return None



# --- Load lipid metadata and insert cross-references ---
def load_lipid_metadata(metadata_path, database):
    with open(metadata_path, 'r') as f:
        meta = yaml.safe_load(f)

    lipid_LipidInfo = meta.get('NMRlipids', {})
    bioschema = meta.get('bioschema_properties', {})
    sameas = meta.get('sameAs', {})

    # Insert lipid into lipids table
    molecule_id = lipid_LipidInfo.get('id', '')
    if not molecule_id:
        raise ValueError(f"Error in metadata path {metadata_path}Lipid ID cannot be empty")
        
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



def check_exp(exp, README) -> bool:
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

    if args.debug: print(f"Processing experiment at path: {exp}")
    if (not README):
        print(f"WARNING: README.yaml in experiment path '{exp}' is empty or invalid. Skipping experiment.", file=sys.stderr)
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

def load_experiment_composition(Exp_ID, README, ExpInfo=None) -> None:
    '''
    Load membrane and solution composition for an experiment.
    
    Parameters
    ----------
    Exp_ID : int
        The experiment ID to link compositions to.
    README : dict
        The README metadata containing composition information.
    Returns
    -------
    None
    '''
    # Load membrane composition
    for lipid_name, lipid_data in README.get("MEMBRANE_COMPOSITION", README.get("MOLAR_FRACTIONS", {})).items():
        lipid_id = UPSERT(database, 'lipids', {'molecule': lipid_name})
        if ExpInfo and ExpInfo.get('type') == 'OP':
            # For OP experiments, we store OP data from the json file
            # find the json file in the experiment path and store its contents in the DB
            op_path = osp.join(PATH_EXPERIMENTS_OP, ExpInfo['path'],f"{lipid_name}_Order[_]*Parameters.json")
            files = glob.glob(op_path)
            if not files:
                print(f"WARNING: No order parameter JSON file found for lipid {lipid_name} in experiment path '{op_path}'", file=sys.stderr)
                continue
            op_json_file = files.pop()
            if args.debug: print(f"Loading order parameter data from {op_json_file} for lipid {lipid_name}")
            with open(op_json_file, 'r') as f:
                op_data = json.load(f)
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

def load_experiment_properties(id, data) -> None:
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
        prop_id = CreateEntry('experiment_property', prop_data)
        # Link experiment and property
        if args.debug: print ("Linking property {}:{} to experiment ID {}".format(prop_id,prop, id))
        LinkEntries('experiments_properties_linker', {'experiment_id': id, 'property_id': prop_id})
        
# List to store failed entries

FAILS = []

# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# MAIN PROGRAM
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

if __name__ == '__main__':

    # Load the configuration of the connection
    config = json.load(open(args.config, "r"))
    database = pymysql.connect(**config)

    # Load the lipid and experiment metadata and cross-references only if no systems specified
    if not args.systems:
    # Load lipid metadata and cross-references
        data_path = os.path.join(NMLDB_MOL_PATH, 'membrane')
        for path, _, files in os.walk(data_path):
            for file in files:
                if file.endswith("metadata.yaml"):
                    metadata_path = os.path.join(path, file)
                    if args.debug: print(f"Loading metadata from {metadata_path}")
                    load_lipid_metadata(metadata_path, database)


# -- TABLE `experiments`

        # Find files with order parameters experiments
        EXP_OP = []
        PATH_EXPERIMENTS_OP = osp.join(NMLDB_EXP_PATH, "OrderParameters")

        # Get the path to every README.yaml file with experimental data
        for path, _, files in os.walk(PATH_EXPERIMENTS_OP):
            for file in files:
                if file == "README.yaml":
                    EXP_OP.append(osp.relpath(path, PATH_EXPERIMENTS_OP))
                    continue

        # Iterate over each experiment
        for expOP in EXP_OP:
            # Get the DOI of the experiment and the path to the README.yaml file
            with open(osp.join(PATH_EXPERIMENTS_OP, expOP, 'README.yaml')) as File:
                README = yaml.load(File, Loader=yaml.FullLoader)
            if not check_exp(expOP, README):
                continue
            section_from_path = os.path.basename(os.path.normpath(expOP))         
            ExpInfo = {
                "article_doi": README.get("ARTICLE_DOI", README.get("DOI", ""))  ,
                "data_doi": README.get("DATA_DOI", ""),
                "section" : README.get("SECTION", section_from_path),
                "type" : "OP",
                "path": expOP
            }
            # Entry in the DB with the LipidInfo of the experiment
            Exp_ID = UPSERT(database, 'experiments', ExpInfo)
            if args.debug: print ("Inserted experiment {} of type OP".format(Exp_ID))
            # Now add the membrane composition if available
            load_experiment_composition(Exp_ID, README, ExpInfo=ExpInfo)
            load_experiment_properties(Exp_ID, README)
 
    # -- TABLE `experiments_FF`
        # Find files with form factor experiments
        EXP_FF = []
        PATH_EXPERIMENTS_FF = osp.join(NMLDB_EXP_PATH, "FormFactors")

        # Get the path to every README.yaml file with experimental data
        for path, _, files in os.walk(PATH_EXPERIMENTS_FF):
            for file in files:
                if file == "README.yaml":
                    EXP_FF.append(osp.relpath(path, PATH_EXPERIMENTS_FF))
                    continue

        # Iterate over each experiment
        for expFF in EXP_FF:
            # Get the DOI of the experiment and the path to the README.yaml file
            with open(osp.join(PATH_EXPERIMENTS_FF, expFF, 'README.yaml')) as File:
                README = yaml.load(File, Loader=yaml.FullLoader)
            section_from_path = os.path.basename(os.path.normpath(expFF))
            if not check_exp(expFF, README):
                continue
            exp_path_full = osp.join(PATH_EXPERIMENTS_FF, expFF)
            # Load form factor data file (assuming only one .json file per experiment)
            form_factor_files = glob.glob(osp.join(exp_path_full, '*.json'))
            form_factor_data = None
            if form_factor_files:
                if args.debug: print(f"Found form factor data files in experiment path '{exp_path_full}': {form_factor_files}")
                with open(form_factor_files[0], 'r') as ff_file:
                    form_factor_data = json.load(ff_file)
                # You can process form_factor_data as needed here  
            else:
                print(f"WARNING: No form factor data files found in experiment path '{exp_path_full}'")
            
            ExpInfo = {
                        "article_doi": README.get("ARTICLE_DOI", README.get("DOI", ""))  ,
                        "data_doi": README.get("DATA_DOI", ""),
                        "section" : README.get("SECTION", section_from_path),
                        "type" : "FF",
                        "data": json.dumps(form_factor_data) if form_factor_data else None,
                        "path": expFF
                    }
            # Entry in the DB with the LipidInfo of the experiment
            Exp_ID = UPSERT(database, 'experiments', ExpInfo)
            if args.debug: print ("Inserted experiment {} of type FF".format(Exp_ID))
            # Now add the membrane composition if available
            load_experiment_composition(Exp_ID, README, ExpInfo=ExpInfo)
            load_experiment_properties(Exp_ID, README)


                    
                    
  
    # -- TABLE `forcefields`, `lipids_forcefields` and others
    
    systems = dbl.core.initialize_databank()
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
            PATH_SIMULATION = osp.join(NMLDB_SIMU_PATH, README["path"])

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
            #"ERROR: The forcefield name is missing or invalid in the Simulation README file." + PATH_SIMULATION
            #assert "FF_DATE" in README and README["FF_DATE"] , \
            #"ERROR: The forcefield date is missing in the Simulation README file."  + README["path"]   
            #assert "FF_SOURCE" in README, \
            #"ERROR: The forcefield source is missing in the Simulation README file." + README["path"]   
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

    # --- TEMPORARY -----
    # Must be chenged when the final structure is ready (???)
    # If the LIPID_FragmentQuality.json file will be defined for every system
    # this part can be deleted and just read the quality (try at the end of this
    # part). At this moment the ranking is not necessary in the DB, the web
    # already provides the result sorted by quality.
                    PATH_RANKING = osp.join(NMLDB_DATA_PATH, "Ranking",)
                    Lipid_Ranking[key] = {}
                    # Find the position of the system in the ranking
                    for file in glob.glob(osp.join(PATH_RANKING, key) + "*"):

                        if args.debug: print("Processing ranking:", file)

                        # Kind of ranking (total, headgroup...)
                        kind = re.search('_(.*)_', file).group(1)

                        # Open the ranking file
                        with open(file) as FILE:
                            RANKING_LIST = json.load(FILE)

                            # Find the position of the system in the ranking
                            for SIM in range(len(RANKING_LIST)):

                                if README["path"] in \
                                        RANKING_LIST[SIM]["system"]["path"]:
                                    Lipid_Ranking[key][kind] = SIM + 1

                                    if Store:
                                        Lipid_Quality[key] = RANKING_LIST[SIM][key]
                                        Store = False

                            # If it does not have an assigned value, use None
                            if kind not in Lipid_Ranking[key]:
                                Lipid_Ranking[key][kind] = 0
                    # If the ranking file was not found, set all values to 0
                    # Read the quality file of the lipid (this will remain if the rest
                    # is removed)
                    try:
                        with open(osp.join(PATH_SIMULATION, key +
                                           '_FragmentQuality.json')) as FILE:
                            Lipid_Quality[key] = json.load(FILE)
                    except Exception:
                        Lipid_Quality[key] = {
                            "total": 0,
                            "headgroup": 0,
                            "sn-1": 0,
                            "sn-2": 0}

                    for t in ["total", "headgroup", "sn-1", "sn-2"]:
                        try:
                            Lipid_Quality[key][t] = Lipid_Quality[key][t]\
                                if not np.isnan(Lipid_Quality[key][t]) else 0
                        except Exception:
                            Lipid_Quality[key][t] = 0
    # ------------------

    # -- TABLE `ions`
            # Empty dictionary for the LipidInfo of the ions
            Ions = {}
            # Find the ions in the composition
            for key in README["COMPOSITION"]:
                if key in NMRDict.molecules_set and key != "SOL": 
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
            except Exception:
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
            except Exception:
                QUALITY_SYSTEM = {
                    "total": 0,
                    "headgroup": 0,
                    "tails": 0}

            try:
                FFExp = genRpath(
                    osp.join(PATH_EXPERIMENTS_FF, README["EXPERIMENT"]["FORMFACTOR"]))
            except Exception:
                FFExp = ''

            # Collect the LipidInformation of the analysis of the trajectory
            LipidInfo = {
                "trajectory_id":          Trj_ID,
                "bilayer_thickness":      BLT,
                "area_per_lipid":         APL,
                "area_per_lipid_file":    genRpath(
                    osp.join(NMLDB_SIMU_PATH, README["path"], 'apl.json')),
                "form_factor_file":       genRpath(
                    osp.join(NMLDB_SIMU_PATH, README["path"], 'FormFactor.json')),
                "quality_total":          QUALITY_SYSTEM["total"],
                "quality_headgroups":     QUALITY_SYSTEM["headgroup"],
                "quality_tails":          QUALITY_SYSTEM["tails"],
                "form_factor_experiment": FFExp,
                "form_factor_quality":    FFQ[0],
                "form_factor_scaling":    FFQ[1]
                }

            # Collect the minimal LipidInformation of the analysis of the trajectory
            Minimal = {"trajectory_id": Trj_ID}

            # Entry in the DB with the LipidInfo of the analysis of the simulation
            _ = UPSERT(database, 'trajectories_analysis', LipidInfo)

    # -- TABLE `trajectories_analysis_lipids`
            for lipid in Lipids:
                try:
                    OPExp = genRpath(osp.join(
                        PATH_EXPERIMENTS_OP,
                        list(README["EXPERIMENT"]["ORDERPARAMETER"][lipid].values())[0],
                        lipid + '_OrderParameters.json')
                        )
                except Exception:
                    OPExp = ''

                # Collect the LipidInformation of each lipid in the simulation
                LipidInfo = {
                    "trajectory_id":                Trj_ID,
                    "lipid_id":                     Lipids_ID[lipid],
                    "quality_total":                Lipid_Quality[lipid]["total"],
                    "quality_hg":                   Lipid_Quality[lipid]["headgroup"],
                    "quality_sn-1":                 Lipid_Quality[lipid]["sn-1"],
                    "quality_sn-2":                 Lipid_Quality[lipid]["sn-1"],
                    "order_parameters_file":        genRpath(
                        osp.join(NMLDB_SIMU_PATH, README["path"],
                                 lipid + 'OrderParameters.json')),
                    "order_parameters_experiment":  OPExp,
                    "order_parameters_quality":     genRpath(
                        osp.join(NMLDB_SIMU_PATH, README["path"],
                                 lipid + '_OrderParameters_quality.json'))
                    }

                # The minimal LipidInformation that identifies the lipid in the simulation
                Minimal = {"trajectory_id": Trj_ID,
                           "lipid_id":      Lipids_ID[lipid]}

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

    # --- TEMPORAL -----
    # The table may be removed in the future, and the quality included in the
    # trajectory_analysis table.

    # -- TABLE `ranking_global`
            # Empty dictionary for the ranking
            
            Ranking = {}
            
            for file in glob.glob(osp.join(PATH_RANKING, "SYSTEM") + "*"):

                # Type of ranking
                kind = re.search('_(.*)_', file.split("/")[-1]).group(1)

                # Open the ranking file
                with open(file) as FILE:
                    RANKING_LIST = json.load(FILE)

                    # Find the position of the system in the ranking
                    for SIM in range(len(RANKING_LIST)):
                        if README["path"] in RANKING_LIST[SIM]["system"]["path"]:
                            Ranking[kind] = SIM + 1

                    # If it does not have an assigned value, use None
                    if kind not in Ranking:
                        Ranking[kind] = 4242
            """
            """
            # Collect the LipidInformation of the position of the system in the ranking
            LipidInfo = {
                "trajectory_id": Trj_ID,
                "ranking_total": Ranking["total"],
                "ranking_hg":    Ranking["headgroup"],
                "ranking_tails": Ranking["tails"],
                "quality_total": QUALITY_SYSTEM["total"],
                "quality_hg":    QUALITY_SYSTEM["headgroup"],
                "quality_tails": QUALITY_SYSTEM["tails"]
                }

            # The minimal LipidInformation about the system
            Minimal = {"trajectory_id": Trj_ID}

            # Entry in the DB with the LipidInfo of ranking
            _ = UPSERT(database, 'ranking_global', LipidInfo)
    # ------------------
    # -- TABLE `ranking_lipids`
            # Empty dictionary for the ranking
            Ranking_lipids = {}
            
            for lipid in Lipids:
                Ranking_lipids[lipid] = {}

                for file in glob.glob(osp.join(PATH_RANKING, lipid) + "*"):
                    # Type of ranking
                    kind = re.search('_(.*)_', file).group(1)

                    # Open the ranking file
                    with open(file) as FILE:
                        RANKING_LIST = json.load(FILE)

                        # Find the position of the system in the ranking
                        for SIM in range(len(RANKING_LIST)):
                            if README["path"] in RANKING_LIST[SIM]["system"]["path"]:
                                Ranking_lipids[lipid][kind] = SIM + 1

                for t in ["total", "headgroup", "sn-1", "sn-2"]:
                    try:
                        Ranking_lipids[lipid][t] = Ranking_lipids[lipid][t] \
                            if not np.isnan(Ranking_lipids[lipid][t]) else 4242
                    except Exception:
                        Ranking_lipids[lipid][t] = 4242

                # Collect the LipidInformation of the position of the system in the ranking
                LipidInfo = {
                    "trajectory_id": Trj_ID,
                    "lipid_id":      Lipids_ID[lipid],
                    "ranking_total": Ranking_lipids[lipid]["total"],
                    "ranking_hg":    Ranking_lipids[lipid]["headgroup"],
                    "ranking_sn-1":  Ranking_lipids[lipid]["sn-1"],
                    "ranking_sn-2":  Ranking_lipids[lipid]["sn-2"],
                    "quality_total": Lipid_Quality[lipid]["total"],
                    "quality_hg":    Lipid_Quality[lipid]["headgroup"],
                    "quality_sn-1":  Lipid_Quality[lipid]["sn-1"],
                    "quality_sn-2":  Lipid_Quality[lipid]["sn-2"]
                    }

                # The minimal LipidInformation about the system
                Minimal = {"trajectory_id": Trj_ID,
                           "lipid_id":      Lipids_ID[lipid]}

                # Entry in the DB with the LipidInfo of ranking
                _ = UPSERT(database, 'ranking_lipids', LipidInfo)
    # ------------------
            
   
            if "EXPERIMENT" in README:
                if "ORDERPARAMETER" in README.get("EXPERIMENT", {}):
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
                                exp_path = osp.join(PATH_EXPERIMENTS_OP, path)
                                if not osp.exists(exp_path):
                                    print("WARNING: Experiment path does not exist: " +
                                          exp_path + " for system: " +
                                          README["path"], file=sys.stderr)  
                                    continue
                                for file in os.listdir(exp_path):
                                    if file.endswith(".json") and file.startswith(mol + '_'):
                                        if args.debug:
                                            print("Linking trajectory {} with experiment {} for lipid {}".format(
                                                Trj_ID, file, mol))
                                        Linked_Experiments_OP.append(README["path"] + ":" + mol +" ID:" + str(Trj_ID))
                                        exp_id = CheckEntry(
                                                    'experiments_OP', {
                                                      #"article_doi": path,
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
            else:
                if args.debug:
                    print("WARNING: No EXPERIMENT section found for system: " +
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
