import numpy as np
import json
import logging
import os.path as osp
from fairmd.lipids import *
from fairmd.lipids.core import *
from fairmd.lipids.api import *
import fairmd.lipids as dbl

# Configure logging
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger(__name__)

def main():
    # Load systems using initialize_databank() from fairmd.lipids.api
    systems = initialize_databank()
    
    nan_systems = []
    
    for system in systems:
        README = system.readme or {}
        
        # Skip systems without README or TRJLENGTH
        if not README or 'TRJLENGTH' not in README:
            logger.warning(f"Skipping system {system.exp_id}: Missing README or TRJLENGTH")
            continue
            
        # Calculate block size
        block_size = README["TRJLENGTH"] // 1000 if README["TRJLENGTH"] else 1000
        
        logger.debug(f"Processing system: {README.get('path')}")
        logger.debug(f"  Trajectory length: {README['TRJLENGTH']}")
        logger.debug(f"  Block size: {block_size}")

        try:
            # Get APL data with block size
            apl_data = get_ApL_data(system, block_size)
            
            if apl_data is not None:
                # Convert to numpy array if not already
                apl_array = (apl_data.flatten() if isinstance(apl_data, np.ndarray) else np.array(apl_data))
                
                # Check for NaN values
                nan_mask = np.isnan(apl_array)
                if nan_mask.any():
                    logger.warning(f"NaN values found in APL data for system: {README.get('path')}")
                nan_count = np.sum(nan_mask)
                
                if nan_count > 0:
                    total_elements = apl_array.size
                    logger.warning(f"System: {README.get('path')}")
                    logger.warning(f"  Block size: {block_size}")
                    logger.warning(f"  NaN count: {nan_count} / {total_elements}")
                    logger.warning(f"  Percentage: {100 * nan_count / total_elements:.2f}%")
                    logger.warning(f"  Array shape: {apl_array.shape}")
                    
                    nan_systems.append({
                        'path': README.get('path', system.exp_id),
                        'block_size': block_size,
                        'nan_count': nan_count,
                        'total_elements': total_elements,
                        'trj_length': README['TRJLENGTH']
                    })
                else:
                    logger.debug(f"  No NaN values found")
            else:
                logger.warning(f"No APL data returned for system: {README.get('path', system.exp_id)}")
                
        except ValueError as ve:
            logger.error(f"ValueError for system {README.get('path')}: {ve}")
            # Try again without block size
            try:
                apl_data = get_ApL_data(system)
                if apl_data is not None:
                    apl_array = np.array(apl_data)
                    nan_count = np.sum(np.isnan(apl_array))
                    logger.info(f"  Retry without block size - NaN count: {nan_count}")
            except Exception as e2:
                logger.error(f"  Retry also failed: {e2}")
                
        except Exception as e:
            logger.error(f"Error processing system {README.get('path')}: {e}")
    
    # Summary
    logger.info("\n" + "="*60)
    logger.info(f"SUMMARY: Found {len(nan_systems)} systems with NaN values")
    logger.info("="*60)
    
    for sys_info in nan_systems:
        logger.info(f"\nSystem: {sys_info['path']}")
        logger.info(f"  Trajectory length: {sys_info['trj_length']}")
        logger.info(f"  Block size: {sys_info['block_size']}")
        logger.info(f"  NaN count: {sys_info['nan_count']} / {sys_info['total_elements']}")

if __name__ == "__main__":
    main()