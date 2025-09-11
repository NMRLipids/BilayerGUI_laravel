-- Start a transaction to ensure data consistency
START TRANSACTION;

-- Insert dummy forcefields
INSERT INTO `forcefields` (`name`, `date`, `source`) VALUES
('CHARMM36', '2020', 'http://mackerell.umaryland.edu/charmm_ff.shtml'),
('Slipids', '2019', 'http://www.fos.su.se/~sasha/SLipids/');

-- Insert some common lipids
INSERT INTO `lipids` (`molecule`, `name`, `mapping`) VALUES
('POPC', '1-palmitoyl-2-oleoyl-sn-glycero-3-phosphocholine', 'POPC'),
('DPPC', '1,2-dipalmitoyl-sn-glycero-3-phosphocholine', 'DPPC'),
('DOPC', '1,2-dioleoyl-sn-glycero-3-phosphocholine', 'DOPC'),
('POPE', '1-palmitoyl-2-oleoyl-sn-glycero-3-phosphoethanolamine', 'POPE');

-- Link lipids to forcefields
INSERT INTO `lipids_forcefields` (`lipid_id`, `forcefield_id`, `mapping`) VALUES
(1, 1, 'POPC_CHARMM36'),  -- POPC in CHARMM36
(1, 2, 'POPC_SLIPIDS'),   -- POPC in Slipids
(2, 1, 'DPPC_CHARMM36'),  -- DPPC in CHARMM36
(2, 2, 'DPPC_SLIPIDS'),   -- DPPC in Slipids
(3, 1, 'DOPC_CHARMM36'),  -- DOPC in CHARMM36
(3, 2, 'DOPC_SLIPIDS'),   -- DOPC in Slipids
(4, 1, 'POPE_CHARMM36'),  -- POPE in CHARMM36
(4, 2, 'POPE_SLIPIDS');   -- POPE in Slipids

-- Insert properties for the lipids
INSERT INTO `properties` (`name`, `description`, `value`, `unit`, `type`) VALUES
('molecularWeight_POPC', 'Molecular weight of POPC', '760.076', 'g/mol', 'float'),
('formula_POPC', 'Molecular formula of POPC', 'C42H82NO8P', '', 'string'),
('molecularWeight_DPPC', 'Molecular weight of DPPC', '734.039', 'g/mol', 'float'),
('formula_DPPC', 'Molecular formula of DPPC', 'C40H80NO8P', '', 'string'),
('molecularWeight_DOPC', 'Molecular weight of DOPC', '786.113', 'g/mol', 'float'),
('formula_DOPC', 'Molecular formula of DOPC', 'C44H84NO8P', '', 'string'),
('molecularWeight_POPE', 'Molecular weight of POPE', '718.006', 'g/mol', 'float'),
('formula_POPE', 'Molecular formula of POPE', 'C41H78NO8P', '', 'string');

-- Insert database references
INSERT INTO `db` (`name`, `description`, `url_schema`, `version`) VALUES
('LIPIDMAPS', 'LIPID MAPS Structure Database', 'https://www.lipidmaps.org/data/LMSDRecord.php?LMID=', '2023'),
('ChEBI', 'Chemical Entities of Biological Interest', 'https://www.ebi.ac.uk/chebi/searchId.do?chebiId=', '2023'),
('PubChem', 'PubChem Database', 'https://pubchem.ncbi.nlm.nih.gov/compound/', '2023');

-- Insert cross-references for POPC
INSERT INTO `cross_references` (`db_id`, `lipid_id`, `external_id`, `external_url`) VALUES
(1, 1, 'LMGP01010002', 'https://www.lipidmaps.org/data/LMSDRecord.php?LMID=LMGP01010002'),
(2, 1, 'CHEBI:16497', 'https://www.ebi.ac.uk/chebi/searchId.do?chebiId=16497'),
(3, 1, '5497103', 'https://pubchem.ncbi.nlm.nih.gov/compound/5497103');

-- Insert cross-references for DPPC
INSERT INTO `cross_references` (`db_id`, `lipid_id`, `external_id`, `external_url`) VALUES
(1, 2, 'LMGP01010046', 'https://www.lipidmaps.org/data/LMSDRecord.php?LMID=LMGP01010046'),
(2, 2, 'CHEBI:17517', 'https://www.ebi.ac.uk/chebi/searchId.do?chebiId=17517'),
(3, 2, '5497104', 'https://pubchem.ncbi.nlm.nih.gov/compound/5497104');

-- Link lipids with their properties
INSERT INTO `lipid_properties` (`lipid_id`, `property_id`) VALUES 
(1, 1), -- POPC molecular weight
(1, 2), -- POPC formula
(2, 3), -- DPPC molecular weight
(2, 4), -- DPPC formula
(3, 5), -- DOPC molecular weight
(3, 6), -- DOPC formula
(4, 7), -- POPE molecular weight
(4, 8); -- POPE formula

COMMIT;
