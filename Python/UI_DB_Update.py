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
import json
import yaml
import pymysql
import math
import argparse
import numpy as np
import numbers
from loguru import logger as mylogger
from fairmd.lipids import *
from fairmd.lipids.core import *
from fairmd.lipids.api import *
import fairmd.lipids as dbl
import fairmd.lipids.core as NMRDict
from fairmd.lipids.molecules import *
from fairmd.lipids.experiment import ExperimentCollection, ExperimentError
from fairmd.lipids.auxiliary.opconvertor import build_nice_OPdict


# most of paths should be inserted into the DB relative to repo root
def genRpath(apath):
    return osp.relpath(apath, dbl.FMDL_DATA_PATH)

# Helper function to replace NaN with None
def rnan(x):
    return None if isinstance(x, float) and math.isnan(x) else x

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

# Strict systems mode
parser.add_argument(
    "--strict_systems", action='store_true',
    help=''' Strict mode for systems: raise errors if fields are missing in the README.
    Default: %(default)s ''')

# Strict experiments mode
parser.add_argument(
    "--strict_experiments", action='store_true',
    help=''' Strict mode for experiments: raise errors if fields are missing in the README.
    Default: %(default)s ''')  

parser.add_argument(
        "--level", 
        default="INFO", 
        choices=["TRACE", "DEBUG", "INFO", "WARNING", "ERROR", "CRITICAL"],
        help="Set the logging level (default: INFO)"
    )

# Debug mode
parser.add_argument(
    "-d", "--debug", type=int, default=0,
    help=''' Activate the debug mode. Default: %(default)s ''')     

args = parser.parse_args()
logger = mylogger

if args.level.upper() == "TRACE":
    logger = mylogger.bind(name="UI_DB_Update")

logger.remove()  # Remove default logger
# This format removes the module (__main__), function (<module>), and line number (93)
# It only shows the timestamp (optional) and the message.
logger.add(sys.stderr, 
    format="<green>{time:HH:mm:ss}</green> | <level>{level: <8}</level> | {message}",
    level=args.level.upper(), 
)


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
        # Print query for trace mode only
        logger.trace(f"Executing UPSERT on table {table} with data {data}")
        query = cursor.mogrify(sql, values)
        logger.trace("Prepared Query String:")
        logger.trace(query)
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
            logger.exception("Error executing query: {}".format(err))
            logger.debug(composed_query_str)
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
        logger.debug(f"Creating entry in {Table} with values {LipidInformation}")
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
    logger.debug(f"Inserted/Updated lipid {molecule_id} with ID {lipid_id}")

    # Insert synonyms
    synonyms = bioschema.get('alternateNames', [])
    for synonym in synonyms:
        synonym_data = {
            'lipid_id': lipid_id,
            'synonym': synonym
        }
        UPSERT(database, 'lipids_synonyms', synonym_data)
        logger.debug(f"Inserted synonym {synonym} for lipid ID {lipid_id}") 
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
        logger.debug(f"Linked property {prop} to lipid ID {lipid_id}")

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
        logger.debug(f"Inserted cross-reference to {db_name} with external ID {ext_id} for lipid ID {lipid_id}")



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
    logger.debug(f"Processing experiment at path: {exp}")
    if (not README):
        logger.warning(f"WARNING: Empty metadata for path '{exp}' is. Skipping experiment.")
        return False
    section_from_path = os.path.basename(os.path.normpath(exp))
    section_from_readme = README.get("SECTION")
    if section_from_readme:
        if str(section_from_readme) != str(section_from_path):
            logger.warning(f"WARNING: Section in README ('{section_from_readme}') does not match section from path ('{section_from_path}') in experiment path '{exp}'. Skipping experiment.")
            return False
    # check if experiment path follows expected structure doi1/doi2/section
    if exp.count('/') != 2:
        logger.warning(f"WARNING: Experiment path '{exp}' does not follow expected structure (doi1/doi2/section). Skipping experiment.")
        return False
    # check if section is numeric, skip if not
    if not section_from_path.isdigit():
        logger.warning(f"Section '{section_from_path}' in experiment path '{exp}' is not numeric. Skipping experiment.")
        return False
    if not README.get("ARTICLE_DOI") and not README.get("DOI"):
        logger.warning(f"ARTICLE_DOI is missing in README.yaml in experiment path '{exp}'. Skipping experiment.")
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
        op_data = {}
        if ExpInfo and ExpInfo.get('type') == 'OP':
            # For OP experiments, read OP data from the experiment object
            # Access the `data` attribute separately so we can handle
            # errors raised by the property accessor (e.g. ExperimentError). 
            _data = None          
            try:
                _data = expobj.data
            except ExperimentError as e:
                    logger.warning(f"Problem reading OP data for lipid {lipid_name}: {e}")
                    if args.strict_experiments:
                        raise e
            if isinstance(_data, dict):
                op_data = _data.get(lipid_name, {})
            else:
                try:
                    # Some experiments may have no data at all, or data in an unexpected format. Handle this gracefully.
                    op_data = _data[lipid_name] 
                except (TypeError, KeyError, IndexError, AttributeError) as exc:
                    logger.warning(f"Problem reading OP data for lipid {lipid_name}")
                    #logger.exception(exc)
                    if args.strict_experiments:
                        raise exc
                    op_data = {}       
        if op_data:
            lipid_object = Lipid(lipid_name)
            lipid_object.register_mapping()
            print(f"Nice_OP_dict: for lipid {lipid_name} in experiment {README.get('DOI', 'unknown')}")
            try:
                op_data = build_nice_OPdict(op_data, lipid_object)
            except Exception as e:
                logger.warning(f"Problem building OP dict in experiment {README.get('DOI', 'unknown')} for lipid {lipid_name}")
                #logger.exception(e)
                if args.strict_experiments:
                    raise e
                op_data = {}    
                
        comp_data = {
            'experiment_id': Exp_ID,
            'lipid_id': lipid_id,
            'mol_fraction': float(lipid_data),
            'data': json.dumps(op_data) if op_data and ExpInfo and ExpInfo.get('type') == 'OP' else None,
        }
        UPSERT(database, 'experiments_membrane_composition', comp_data)
        logger.debug(f"Linked lipid {lipid_name} to experiment {Exp_ID}, {lipid_data}")
    
    # Load solution composition
    for compound_name, compound_data in (README.get("SOLUTION_COMPOSITION", README.get("ION_CONCENTRATIONS", {})) or {}).items():
        ion_comp_data = {
            'experiment_id': Exp_ID,
            'compound': compound_name,
            'concentration': float(compound_data),
        }
        UPSERT(database, 'experiments_solution_composition', ion_comp_data)
        logger.debug(f"Linked ion {compound_name} to experiment {Exp_ID}, {compound_data}")

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
        logger.debug(f"Linking property {prop_id}:{prop} to experiment ID {id}")
        LinkEntries('experiments_properties_linker', {'experiment_id': id, 'property_id': prop_id})
        


# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
# MAIN PROGRAM
# =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

if __name__ == '__main__':
    
    # List to store failed entries
    FAILS = []
    lipids_counts = 0
    experiments_op_counts = 0
    experiments_ff_counts = 0
    systems_counts = 0
    propper_op_count = 0
    systems_with_issues_counts = 0

    # Define the expected fields in the README for systems. 
    # These are used to check the completeness of the README and
    # raise warnings or errors if fields are missing.
    # The STRICT_SYSTEM_FIELDS list includes 'ID' as a required field, while SYSTEM_FIELDS does not.
    # The STRICT_SYSTEM_FIELDS list does not include WARNINGS, while SYSTEM_FIELDS does. 
    # In strict mode, the absence of any of these fields will raise an error. 
    # In non-strict mode, it will only log a warning.  
    
    SYSTEM_FIELDS = [
        'AUTHORS_CONTACT', 'COMPOSITION', 'CPT', 'DATEOFRUNNING', 
        'DOI', 'FF', 'FF_DATE', 'FF_SOURCE', 'GRO', 'LOG',
        'NUMBER_OF_ATOMS', 'PREEQTIME', 'PUBLICATION', 'SOFTWARE',
        'SOFTWARE_VERSION', 'SYSTEM', 'TEMPERATURE', 'TIMELEFTOUT', 'TOP',
        'TPR', 'TRAJECTORY_SIZE', 'TRJ', 'TRJLENGTH', 'TYPEOFSYSTEM',
        'WARNINGS'
    ]

    STRICT_SYSTEM_FIELDS = [
        'AUTHORS_CONTACT', 'COMPOSITION', 'CPT', 'DATEOFRUNNING', 
        'DOI', 'FF', 'FF_DATE', 'FF_SOURCE', 'GRO', 'LOG',
        'NUMBER_OF_ATOMS', 'PREEQTIME', 'PUBLICATION', 'SOFTWARE',
        'SOFTWARE_VERSION', 'SYSTEM', 'TEMPERATURE', 'TIMELEFTOUT', 'TOP',
        'TPR', 'TRAJECTORY_SIZE', 'TRJ', 'TRJLENGTH', 'TYPEOFSYSTEM', 'ID'
    ]


    # Load the configuration of the connection
    config = json.load(open(args.config, "r"))
    database = pymysql.connect(**config)

    # Load the lipid and experiment metadata and cross-references only if no systems specified
    if not args.systems:
        if True:
            logger.info("Loading lipid metadata and cross-references")
        # Load lipid metadata and cross-references
            lipids = lipids_set
            for lipid in lipids:
                load_lipid_metadata(lipid, database)
                lipids_counts += 1

# -- TABLE `experiments`
# Iterate over each experiment for types OP and FF
        logger.info("Starting the processing of the experiments.")       
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
                            "path": exp.exp_id
                        }
                # Entry in the DB with the LipidInfo of the experiment
                exp_ID = UPSERT(database, 'experiments', expInfo)
                logger.debug(f"Inserted experiment {exp_ID} of type {exp_type[:2]}")
                # Now add the membrane composition if available
                load_experiment_composition(database, exp_ID, exp, ExpInfo=expInfo)
                load_experiment_properties(database, exp_ID, exp)
                if exp_type == 'OPExperiment':
                    experiments_op_counts += 1
                else:
                    experiments_ff_counts += 1


    # Load the systems to be processed
    
    systems = initialize_databank()
    Skipped_Systems_FF = []
    Skipped_Systems_AUTHOR = []
    Linked_Experiments_OP = []
    Linked_Experiments_FF = []

    # Iterate over the loaded systems
    
    if args.systems:
        logger.info("Only the following systems will be processed:")
        logger.info(args.systems)


    # Iterate over the loaded systems/simulations
    # We need to process first the forcefields and lipids_forcefields
    # Specify the FMDL_SIMU_PATH from the environment variable
    FMDL_SIMU_PATH = os.getenv('FMDL_SIMU_PATH', dbl.FMDL_SIMU_PATH)

    for system in systems:
        has_issues = False
        # Get the README metadata
        README = system.readme or {}
        if args.force:
            # In force mode, ignore systems with missing README, just print warnings
            try:
                assert README, "ERROR: README is empty for system: " + system.exp_id
                assert 'ID' in README, "ERROR: 'ID' field is missing in README for system: " + system.exp_id
            except AssertionError as e:
                logger.error(e)
                continue
        else:
            # In normal mode, raise errors in the README4
            assert README, "ERROR: README is empty for system: " + system.exp_id
            assert 'ID' in README, "ERROR: 'ID' field is missing in README for system: " + system.exp_id


        if args.systems:
            if README["path"] not in args.systems:
                continue
        try:
            # if True:
            logger.debug("\nCollecting data from system:")
            logger.debug("System path: " + README["path"] + "\n")

            # The location of the files
            PATH_SIMULATION = osp.join(FMDL_SIMU_PATH, README["path"])

            # In the case a field in the README does not exist, set its value to 0
            
            if not args.strict_systems:
                # In force mode, ignore missing fields in the README
                # ID field is already checked above and must exist
                for field in SYSTEM_FIELDS:
                    if field not in README:
                        README[field] = None
            else:   
                # In normal mode, raise errors for missing fields in the README 
                for field in STRICT_SYSTEM_FIELDS:
                    assert field in README, \
                    "ERROR: Field '" + field + "' is missing in the README file. " + \
                    "Check the README file in system: " + README["path"] + "\n"
            # Now check for FF and AUTHORS_CONTACT fields
            
            if not README["FF"] and not args.strict_systems:
                # Skip this system if the forcefield is not defined and we are in force mode, just print a warning
                logger.warning("The forcefield is not defined in the README file. ")
                logger.warning("System: " + README["path"] + "\n")
                README["FF"] = "Unknown"
                has_issues = True
            else: 
                if not README["FF"]:
                    logger.error("The forcefield is not defined in the README file. ")
                    logger.error("System: " + README["path"] + "\n")
                    raise ValueError("Forcefield is not defined in README.")
            if not README["AUTHORS_CONTACT"] and not args.strict_systems:
                logger.warning("The AUTHORS_CONTACT is not defined in the README file. ")
                logger.warning("System: " + README["path"] + "\n")
                README["AUTHORS_CONTACT"] = "Unknown"
                has_issues = True
            else:
                if not README["AUTHORS_CONTACT"]:
                    logger.error("The AUTHORS_CONTACT is not defined in the README file. ")
                    logger.error("System: " + README["path"] + "\n")
                    raise ValueError("AUTHORS_CONTACT is not defined in README.")

    # -- TABLE `forcefields`
            # Collect the LipidInfo of the FF           
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
                        logger.warning("Lipid {} not found in the DB. Adding it.".format(key))  
                        # If it does not exist, create it
                        Lip_ID = UPSERT(database, 'lipids', LipidInfo)
                        logger.debug(f"Inserted lipid {key} with ID {Lip_ID}")
                        lipids_counts += 1
                        has_issues = True
                    
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
                logger.debug(f"Processing lipid in membrane: {lipid}, {Lipids[lipid]}")
                if len(Lipids[lipid]) != 2:
                    if not args.strict_systems:
                        logger.warning("Lipid COUNT fields must be a list of two values " +
                                       "for leaflet 1 and leaflet 2 respectively. " +
                                       "Check the COMPOSITION field in the README file. " +
                                       PATH_SIMULATION + "\n" +
                                       "Using [0,0] as drop in replacement which is BAD!")
                        Lipids[lipid] = [0, 0]
                        has_issues = True
                    else:
                        raise ValueError("ERROR: Lipid COUNT fields must be a list of two values " +
                                    "for leaflet 1 and leaflet 2 respectively. " +
                                    "Check the COMPOSITION field in the README file. " +
                                    PATH_SIMULATION)    
                if Lipids[lipid][0]:
                    Names[0].append(lipid)
                    Number[0].append(str(Lipids[lipid][0]))
                if Lipids[lipid][1]:
                    Names[1].append(lipid)
                    Number[1].append(str(Lipids[lipid][1]))

                # now try to find the lipid in the DB, if it does not exist, add it with the information we have (at least the name)
                if not CheckEntry('lipids', {"molecule": lipid}):
                    if args.strict_systems:
                        raise ValueError("Lipid {} not found in the DB. Please add it to the DB before processing this system.".format(lipid))
                    logger.warning("Lipid {} not found in the DB. Adding it.".format(lipid))  
                    # If it does not exist, create it
                    LipidInfo = {
                        "molecule":      lipid,
                        "name":          lipid,
                        "mapping":       None
                        }
                    Lip_ID = UPSERT(database, 'lipids', LipidInfo)
                    logger.debug(f"Inserted lipid {lipid} with ID {Lip_ID}")
                    count_lipids += 1
                    has_issues = True

                    # Enteer an entry in the trajectories_lipids table for this lipid and trajectory
                    # This is needed to link the trajectory with the lipids in the composition, and to be able to search for trajectories with specific lipids in the future
                    # The entry will be created later, after creating the trajectory entry in the trajectories table, when we have the trajectory ID (Trj_ID)
                   

            Names = [':'.join(Names[0]), ':'.join(Names[1])]
            Number = [':'.join(Number[0]), ':'.join(Number[1])]



            # This is kept for backward compatibility with the old README files
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
                if not args.strict_systems:
                    logger.warning("COMPOSITION section is missing or invalid in the README file. ")
                    logger.warning("Using empty composition as drop in replacement which is BAD! Check README file in " + README["path"] + "\n")
                    README["COMPOSITION"] = {}
                    has_issues = True
                else:
                    raise ValueError( 
                        "ERROR: COMPOSITION section is mandatory (without --force) and must be a dictionary of lipids\n" +
                        "Check the simulation README file in " + PATH_SIMULATION)
            if "SOL" not in README["COMPOSITION"] and not args.strict_systems:
                logger.warning("Water is missing in the composition. ")
                logger.warning("Using Implitcit. Check README file in " + README["path"] + "\n")
                has_issues = True
            else:
                if "SOL" not in README["COMPOSITION"]:
                    raise ValueError( 
                        "ERROR: Water (SOL) must be defined in the COMPOSITION section (without --force)\n" +
                        "Check the simulation README file in " + PATH_SIMULATION)     

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

            # Entry in the DB with the LipidInfo of the trajectory
            Trj_ID = UPSERT(database, 'trajectories', trajectoryInfo)

    # -- TABLE `trajectories_lipids`
            TrjL_ID = {}
            for lipid in Lipids:
                # Collect the LipidInformation of each lipid in the simulation
                LipidInfo = {
                    "trajectory_id": Trj_ID,
                    "lipid_id":      Lipids_ID[lipid],
                    "leaflet_1":     Lipids[lipid][0], 
                    "leaflet_2":     Lipids[lipid][1]
                    }

                # Entry in the DB with the LipidInfo of the lipids in the simulation
                TrjL_ID[lipid] = UPSERT(database, 'trajectories_lipids', LipidInfo)

    
    # -- TABLE `trajectories_ions`
            TrjI_ID = {}
            for ion in Ions:
                logger.debug(f"Processing ion in trajectory: {ion}, {Ions[ion]}")
                
                if len(Ions[ion]) != 2:
                    if not args.strict_systems:
                        logger.warning("Ion counts must be a list of two values " +
                                       "for leaflet 1 and leaflet 2 respectively. " +
                                       "Check the COMPOSITION field in the README file. " +
                                       PATH_SIMULATION + "\n" +
                                       "Using [0,0] as drop in replacement which is BAD!")
                        Ions[ion] = [Ions[ion][0], [0, 0]]
                        has_issues = True
                    else:
                        raise ValueError("ERROR: Ion counts must be a list of two values " +
                                       "for leaflet 1 and leaflet 2 respectively. " +
                                       "Check the COMPOSITION field in the README file. " +
                                       PATH_SIMULATION)

                # Collect the LipidInformation of each ion in the simulation
                LipidInfo = {
                    "trajectory_id": Trj_ID,
                    "ion_id":        Ions[ion][0],
                    "ion_name":      ion,
                    "number":        Ions[ion][1]}

               
                # Entry in the DB with the LipidInfo of the ions in the simulation
                TrjI_ID[ion] = UPSERT(database, 'trajectories_ions', LipidInfo)

   
    # -- TABLE `trajectories_membranes``

            trajectories_membranesInfo = {
                "trajectory_id": Trj_ID,
                "membrane_id": Mem_ID,
                "name": README["SYSTEM"]}
            _ = UPSERT(database, 'trajectories_membranes', trajectories_membranesInfo)

    # -- TABLE `trajectories_analysis`
            # Get the bilayer thickness
            try:
                BLT = get_thickness(system)
            except Exception as e:
                has_issues = True
                if args.debug:
                    logger.warning("Could not compute bilayer thickness.")
                    logger.warning("Exception: {}".format(e))
                if args.strict_systems:
                    raise e
                BLT = None

            # Find the mean area per lipid
            try:
                APL = get_mean_ApL(system)
            except Exception as e:
                has_issues = True
                logger.warning("Could not compute area per lipid.")
                logger.warning("Exception: {}".format(e))
                if args.strict_systems:
                    raise e
                APL = None

            # Form factor quality
            FFQ = get_quality(system, part = 'total', lipid = None, experiment = 'FF')
                
            ## get apldata from the API 
            apl_data = None 
            ## calculate a reasonable block size for the ApL calculation based on the trajectory length, 
            #with a default of 1000 frames if the trajectory length is not available or is zero 
            block_size = README["TRJLENGTH"] // 1000 if README["TRJLENGTH"] else 1000 

            try: 
                apl_data = get_ApL_data(system, block_size) 

            except ValueError as ve:
                # possibly, there are not enough frames to calculate ApL data with the calculated block size. Try again with all points.
                try: 
                    apl_data = get_ApL_data(system)
                except Exception as e: 
                    logger.warning("Could not get area per lipid data.")
                    logger.warning("Exception: {}".format(e))
                    has_issues = True
                    raise e
                    if args.strict_systems:
                        raise e
                    apl_data = None
            
            apl_data = apl_data.tolist()  # Convert numpy array to Python list
            
            if not apl_data and args.strict_systems:
                raise RuntimeError("empty ApL data")
            # apl_data = list(map(lambda x: [rnan(x[0]), rnan(x[1])] if x and len(x) == 2 else None, apl_data))   # Ensure no NaN values in the data, as they cannot be serialized to JSON
            # Collect the LipidInformation of the analysis of the trajectory
            apl_json = None
            try:
                apl_json = json.dumps(apl_data, allow_nan=False) if apl_data else None  # Serialize to JSON, ensuring no NaN values
            except Exception as e:
                apl_data = list(map(lambda x: [rnan(x[0]), rnan(x[1])] if x and len(x) == 2 else None, apl_data))   # Ensure no NaN values in the data, as they cannot be serialized to JSON
                # try json dump again, if it still doesn't work yield the error
                try:
                    apl_json = json.dumps(apl_data, allow_nan=False) if apl_data else None 
                except Exception as e2:
                    logger.warning("Could not serialize area per lipid data to JSON. Check the ApL data for this system.")  
                    logger.warning("System: " + README["path"] + " block size: " + str(block_size) + "\n")
                    has_issues = True
                    if args.strict_systems:
                        raise e2
                    apl_json = None

            ff_data = None
            try:
                ff_data = get_FF(system).tolist() # Convert numpy array to Python list
            except Exception as e:
                logger.warning("Could not get form factor data.")
                logger.warning("Exception: {}".format(e))
                has_issues = True
                if args.strict_systems:
                    raise e
                ff_data = None
            try:               
                ff_json = json.dumps(ff_data, allow_nan=False) if ff_data else None
            except Exception as e:
                logger.warning("Could not serialize form factor data to JSON. Check the form factor data for this system.")  
                logger.warning("System: " + README["path"] + "\n")
                has_issues = True
                if args.strict_systems:
                    raise e
                logger.exception(e)
                ff_json = None
                
            trajectories_analysis_data = {
                "trajectory_id":          Trj_ID,
                "bilayer_thickness":      rnan(BLT) if not isinstance(BLT, list) else [rnan(x) for x in BLT],
                "area_per_lipid":         rnan(APL),
                "area_per_lipid_file":    genRpath(
                    osp.join(FMDL_SIMU_PATH, README["path"], 'apl.json')),
                "area_per_lipid_data": apl_json,
                "form_factor_file":       genRpath(
                    osp.join(FMDL_SIMU_PATH, README["path"], 'FormFactor.json')),
                "form_factor_data": ff_json,
                "op_quality_total":          rnan(get_quality(system, part = 'total', lipid = None, experiment = 'OP')),
                "op_quality_headgroups":     rnan(get_quality(system, part = 'headgroup', lipid = None, experiment = 'OP')),
                "op_quality_tails":          rnan(get_quality(system, part = 'tails', lipid = None, experiment = 'OP')),
                "ff_quality":    rnan(FFQ), # FFQ,
                "ff_scaling":    None, # Not implemented yet
                }

            # Entry in the DB with the LipidInfo of the analysis of the simulation
            _ = UPSERT(database, 'trajectories_analysis', trajectories_analysis_data)

    # -- TABLE `trajectories_analysis_lipids`
        # Get the order parameters data for the system
            op_data = None
            try:
                op_data = get_OP(system)
            except Exception as e:
                logger.warning("Could not get order parameters data.")
                logger.warning("Exception: {}".format(e))
                has_issues = True
                if args.strict_systems:
                    raise e
                op_data = None

            for lipid in Lipids:
                OPExp = ''
                logger.debug("Processing trajectory analysis {} lipid {}".format(system, lipid))               

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
                            logger.warning("Could not generate path for order parameters experiment for lipid {}.".format(lipid))
                            logger.warning("Exception: {}".format(e))
                        has_issues = True
                        if not args.force:
                            raise e
                lipid_obj = system.lipids[lipid]
                #lipid_obj.register_mapping()
                op_plot_data = None

                if op_data and op_data[lipid] and lipid_obj:
                    try:
                        op_plot_data = build_nice_OPdict(op_data[lipid], lipid_obj)
                    except Exception as e:
                        logger.warning("Could not build OP plot data for system {} lipid {}.".format(system, lipid))
                        logger.warning("Exception: {}".format(e))
                        has_issues = True

                if op_plot_data:  
                    try:
                        op_json = json.dumps(op_plot_data, allow_nan=False)
                    except Exception as e:
                        logger.warning("Could not serialize OP plot data for system {} lipid {}.".format(system, lipid))
                        logger.warning("This is likely due to NaN or infinite values in the data. Check the OP data for this system and lipid.")  
                        logger.warning("Exception: {}".format(e))
                        has_issues = True

                        if args.strict_systems:
                            raise e    
                else:
                    op_json = None
                if op_json:
                    logger.debug("Successfully built OP plot data for system {} lipid {}.".format(system, lipid))
                    propper_op_count += 1    
                # Collect the LipidInformation of each lipid in the simulation
                trajectories_analysis_lipids_data = {
                    "trajectory_id":                Trj_ID,
                    "lipid_id":                     Lipids_ID[lipid],
                    "op_quality_headgroups":         rnan(get_quality(system, part = 'headgroup', lipid = lipid, experiment = 'OP')),
                    "op_quality_tails":              rnan(get_quality(system, part = 'tails', lipid = lipid, experiment = 'OP')),
                    "op_quality_total":              rnan(get_quality(system, part = 'total', lipid = lipid, experiment = 'OP')),
                    "order_parameters_file":        genRpath(
                        osp.join(FMDL_SIMU_PATH, README["path"],
                                 lipid + 'OrderParameters.json')),
                    "order_parameters_experiment":  OPExp,
                    "order_parameters_quality":     genRpath(
                        osp.join(FMDL_SIMU_PATH, README["path"],
                                 lipid + '_OrderParameters_quality.json')),
                    "op_plot_data":  op_json
                    }
                
                # Entry in the DB with the LipidInfo of the analysis of the lipid
                # in the simulation
                _ = UPSERT(database, 'trajectories_analysis_lipids', trajectories_analysis_lipids_data)

   
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
    # Link the trajectory with the experiments of type OP and FF associated 
    # to the system in the README file. The association is made through the path of the experiment, 
    # which should be unique. 
    # The experiment must be already in the DB, otherwise it will be 
    # skipped and a warning will be printed.       
    # ------------------            
   
            if "EXPERIMENT" in README and "ORDERPARAMETER" in README.get("EXPERIMENT", {}) and README["EXPERIMENT"]["ORDERPARAMETER"]:
                    # -- TABLE `trajectories_experiments_OP`
                    # The Order Parameters experiments associated to the simulation

                ExpOP = README["EXPERIMENT"]["ORDERPARAMETER"]
                # Iterate over the lipids
                for mol in ExpOP:
                    # Check if there is an experiment associated to the lipid
                    if type(ExpOP[mol]) is list or type(ExpOP[mol]) is dict or len(ExpOP[mol]) > 0:
                        for path in ExpOP[mol]:                              
                            logger.debug("Linking trajectory {} with experiment {} for lipid {}".format(
                                Trj_ID, path, mol))
                            exp_id = CheckEntry(
                                        'experiments_OP', {
                                        "path": path})
                            if not exp_id:
                                if args.strict_systems:
                                    raise ValueError("ERROR: Experiment not found in DB: " +
                                        path + " for system: " +
                                        README["path"])
                                logger.warning("Experiment not found in DB: " +
                                        path + ", referenced in system: " +
                                        system + " for lipid: " + mol)
                                has_issues = True
                                continue # Skip this experiment if it is not found in the DB

                            trajExpLipInfo = {
                                "trajectory_id": Trj_ID,
                                "lipid_id": Lipids_ID[mol],
                                "experiment_id": exp_id,
                            }        
                            _ =  UPSERT(database, 'trajectories_experiments_OP', trajExpLipInfo)
                            if args.debug:
                                logger.debug("Linked trajectory {} with experiment {} for lipid {}".format(
                                    Trj_ID, path, mol))
                            Linked_Experiments_OP.append(README["path"] + ":" + mol +" ID:" + str(Trj_ID))

            else:
                logger.debug("No related order parameter experiments recorded for system: {}".format(system))  
                    
    # -- TABLE `trajectories_experiments_FF`
            if "EXPERIMENT" in README and \
            "FORMFACTOR" in README["EXPERIMENT"] and \
            README["EXPERIMENT"]["FORMFACTOR"]: 
                ExpFF = README["EXPERIMENT"]["FORMFACTOR"]
                if type(ExpFF) is str:
                    ExpFF = [ExpFF]
                elif type(ExpFF) is dict:
                    ExpFF = list(ExpFF.values())
                for path in ExpFF:
                    logger.debug("Linking trajectory {} with FF experiment {}".format(
                        Trj_ID, path))
                    exp_id = CheckEntry(
                                    'experiments_FF', {
                                        #"article_doi": path,
                                    "path": path
                                    })
                    # If the experiment is not found in the DB, skip it and print a warning
                    if not exp_id:
                        if args.strict_systems:
                            raise ValueError("Referenced experiment not found in DB: " +
                                path + " referenced by system: " +
                                system)  
                                # If strict mode is enabled, stop processing the system
                        logger.warning("Referenced FF experiment not found in DB: " +
                                path + " referenced by system: " + system)  
                        has_issues = True        
                        continue # Skip this experiment if it is not found in the DB
                    
                    trajExpInfo = {
                        "trajectory_id": Trj_ID,
                        "experiment_id": exp_id,
                        }

                    _ = UPSERT(database, 'trajectories_experiments_FF',
                                trajExpInfo)
                    logger.debug("Linking trajectory {} with FF experiment {}".format(
                            Trj_ID, path)) 
                    Linked_Experiments_FF.append(README["path"] +" ID:" + str(Trj_ID))
            else:
                logger.debug("No related form factor experiments recorded for system: {}".format(system))
        
        
        
        
            if has_issues:
                systems_with_issues_counts += 1
            systems_counts += 1
        
        # force mode: catch all exceptions, print them, and continue with the next system,
        # while keeping track of the failed systems
        except Exception as err:
            logger.error("Exception loading system: " + README["path"])
            logger.error("Exception loading system:" + README["path"] + "\n" + str(err))
            if args.force:
                logger.exception(err)
            else:
                raise err
            FAILS.append(README["path"])
    
    database.close() 

####################

    logger.success("loaded {} lipids, metadata, and cross-references.".format(lipids_counts))
    logger.success("loaded {} experiments of type OP.".format(experiments_op_counts))
    logger.success("loaded {} experiments of type FF.".format(experiments_ff_counts))
    logger.success("loaded {} systems.".format(systems_counts))
    if propper_op_count:
        logger.success("Properly processed {} system order parameter data.".format(propper_op_count))
    else:
        logger.error("No system order parameter data was properly processed. Check the OP data and the README files of the systems.")
    if systems_with_issues_counts:
        logger.warning("There were {} systems with at least one issue. \n Check the warnings above for details.".format(systems_with_issues_counts)) 
    if FAILS:
        logger.error(
            "\nThe following systems failed. Please check the files." +
            "\n" + "\n".join(FAILS)
            )
    if len(Skipped_Systems_FF) > 0:
        logger.error(
            "\nThe following systems were skipped due to missing forcefield information:" +
            "\n" + "\n".join(Skipped_Systems_FF)
            )
    if len(Skipped_Systems_AUTHOR) > 0:
        logger.error(
            "\nThe following systems were skipped due to missing author information:" +
            "\n" + "\n".join(Skipped_Systems_AUTHOR)
            )
    if len(Linked_Experiments_OP) >= 0:
        logger.info(
            "{} order parameter experiments were linked to simulations.".format(len(Linked_Experiments_OP))
            )
    if len(Linked_Experiments_FF) >= 0:
        logger.info(
            "{} form factor experiments were linked to simulations.".format(len(Linked_Experiments_FF))
            )   
    
####################

