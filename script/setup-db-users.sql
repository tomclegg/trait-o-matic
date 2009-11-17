-- should be run with "mysql --force" so grant statements run even
-- though databses already exist

CREATE DATABASE `genotypes` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `ariel` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `caliban` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `dbsnp` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;
CREATE DATABASE `hgmd_pro` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;
CREATE DATABASE `pharmgkb` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;

REVOKE ALL PRIVILEGES, GRANT OPTION FROM `reader`@`localhost`, `writer`@`localhost`, `updater`@`localhost`, `installer`@`localhost`;

-- "reader" is the back-end user when it needs to look up variants in
-- various db/tables

CREATE USER `reader`@`localhost` IDENTIFIED BY 'shakespeare';
GRANT SELECT ON `ariel`.* TO `reader`@`localhost`;
GRANT SELECT ON `caliban`.* TO `reader`@`localhost`;
GRANT SELECT ON `dbsnp`.* TO `reader`@`localhost`;
GRANT SELECT ON `hgmd_pro`.* TO `reader`@`localhost`;
GRANT SELECT ON `pharmgkb`.* TO `reader`@`localhost`;
GRANT SELECT ON `genotypes`.* TO `reader`@`localhost`;

-- "updater" is the back-end user when it needs to write to the db
-- (hapmap_load_database.py and json_to_job_database.py)

CREATE USER 'updater'@`localhost` IDENTIFIED BY 'shakespeare';
GRANT SELECT, INSERT, UPDATE, DELETE ON `caliban`.* TO `updater`@`localhost`;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, CREATE TEMPORARY TABLES, DROP ON `genotypes`.* TO `updater`@`localhost`;

-- "writer" is the webgui user

CREATE USER 'writer'@`localhost` IDENTIFIED BY 'shakespeare';
GRANT SELECT, INSERT, UPDATE, DELETE ON `ariel`.* TO `writer`@`localhost`;
GRANT SELECT ON `genotypes`.* TO `writer`@`localhost`;

-- "installer" is the install script

CREATE USER 'installer'@`localhost` IDENTIFIED BY 'shakespeare';
GRANT ALL PRIVILEGES ON `ariel`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `caliban`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `dbsnp`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `hgmd_pro`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `genotypes`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `pharmgkb`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT CREATE USER ON *.* TO `installer`@`localhost`;

FLUSH PRIVILEGES;
