def load_systems():
    # Placeholder function to load systems
    # This should be replaced with actual logic to load systems
    return []

def check_nan_values(apl_data):
    if apl_data is None:
        return 0
    return np.isnan(apl_data).sum()

def debug_apl_data(system, block_size, apl_data):
    nan_count = check_nan_values(apl_data)
    if nan_count > 0:
        print(f"Debug Info: System: {system}, Block Size: {block_size}, NaN Values Found: {nan_count}")
    else:
        print(f"Debug Info: System: {system}, Block Size: {block_size}, No NaN Values Found")