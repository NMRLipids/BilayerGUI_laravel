# APL NaN Debugger

## Overview
The APL NaN Debugger is a Python project designed to load systems, retrieve area per lipid (APL) data, and check for NaN values in the retrieved data. This tool is useful for researchers working with lipid simulations, ensuring data integrity and providing insights into the APL calculations.

## Project Structure
```
apl-nan-debugger
├── src
│   ├── debug_apl.py       # Main logic for loading systems and retrieving APL data
│   └── utils.py           # Utility functions for data processing
├── requirements.txt        # Project dependencies
├── config.json             # Configuration settings
└── README.md               # Project documentation
```

## Installation
To set up the project, clone the repository and install the required dependencies:

```bash
git clone <repository-url>
cd apl-nan-debugger
pip install -r requirements.txt
```

## Configuration
Edit the `config.json` file to include the necessary configuration settings, such as database connection details.

## Usage
Run the main script to load systems and retrieve APL data:

```bash
python src/debug_apl.py
```

The script will output debug information regarding the system, block size, and any NaN values found in the APL data.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for details.