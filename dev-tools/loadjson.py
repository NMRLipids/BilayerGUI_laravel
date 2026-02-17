import json
import sys

def validate_json(filepath):
    """
    Validate JSON file and report if it's correct or not.
    
    Args:
        filepath: Path to the JSON file to validate
    """
    try:
        with open(filepath, 'r') as file:
            json.load(file, strict=True)
        print(f"✓ Valid JSON: {filepath}")
        return True
    except FileNotFoundError:
        print(f"✗ Error: File not found: {filepath}", file=sys.stderr)
        return False
    except json.JSONDecodeError as e:
        print(f"✗ Invalid JSON: {filepath}", file=sys.stderr)
        print(f"  Error at line {e.lineno}, column {e.colno}: {e.msg}", file=sys.stderr)
        return False
    except Exception as e:
        print(f"✗ Error: {str(e)}", file=sys.stderr)
        return False

def main():
    if len(sys.argv) != 2:
        print("Usage: python json_parser.py <json_file>", file=sys.stderr)
        sys.exit(1)
    
    filepath = sys.argv[1]
    is_valid = validate_json(filepath)
    sys.exit(0 if is_valid else 1)

if __name__ == "__main__":
    main()