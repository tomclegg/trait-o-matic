-- should be run with "mysql --force" so grant statements run even
-- though databses already exist

CREATE DATABASE `genotypes` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `ariel` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `caliban` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
CREATE DATABASE `dbsnp` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;
CREATE DATABASE `hgmd_pro` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;
CREATE DATABASE `pharmgkb` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;

-- drop non-local users if they exist (from previous installation),
-- first giving them a row in the access table to prevent mysql errors
-- in case they're *not* there

GRANT USAGE ON `ariel`.* TO `reader`@`%`, `writer`@`%`, `updater`@`%`, `installer`@`%`;
DROP USER 'reader'@'%';
DROP USER 'writer'@'%';
DROP USER 'updater'@'%';
DROP USER 'installer'@'%';

-- similarly, drop the local users so we don't get errors while creating them

GRANT USAGE ON `ariel`.* TO `reader`@`localhost`, `writer`@`localhost`, `updater`@`localhost`, `installer`@`localhost`;
DROP USER 'reader'@'localhost';
DROP USER 'writer'@'localhost';
DROP USER 'updater'@'localhost';
DROP USER 'installer'@'localhost';

CREATE USER `reader`@`localhost` IDENTIFIED BY 'shakespeare';
CREATE USER 'updater'@`localhost` IDENTIFIED BY 'shakespeare';
CREATE USER 'writer'@`localhost` IDENTIFIED BY 'shakespeare';
CREATE USER 'installer'@`localhost` IDENTIFIED BY 'shakespeare';

REVOKE ALL PRIVILEGES, GRANT OPTION FROM `reader`@`localhost`, `writer`@`localhost`, `updater`@`localhost`, `installer`@`localhost`;

-- "reader" is the back-end user when it needs to look up variants in
-- various db/tables

GRANT SELECT ON `ariel`.* TO `reader`@`localhost`;
GRANT SELECT ON `caliban`.* TO `reader`@`localhost`;
GRANT SELECT ON `dbsnp`.* TO `reader`@`localhost`;
GRANT SELECT ON `hgmd_pro`.* TO `reader`@`localhost`;
GRANT SELECT ON `pharmgkb`.* TO `reader`@`localhost`;
GRANT SELECT ON `genotypes`.* TO `reader`@`localhost`;

-- "updater" is the back-end user when it needs to write to the db
-- (hapmap_load_database.py and json_to_job_database.py)

GRANT SELECT, INSERT, UPDATE, DELETE ON `caliban`.* TO `updater`@`localhost`;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, CREATE TEMPORARY TABLES, DROP ON `genotypes`.* TO `updater`@`localhost`;

-- "writer" is the webgui user

GRANT SELECT, INSERT, UPDATE, DELETE ON `ariel`.* TO `writer`@`localhost`;
GRANT SELECT ON `genotypes`.* TO `writer`@`localhost`;

-- "installer" is the install script

GRANT ALL PRIVILEGES ON `ariel`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `caliban`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `dbsnp`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `hgmd_pro`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `genotypes`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON `pharmgkb`.* TO `installer`@`localhost` WITH GRANT OPTION;
GRANT CREATE USER ON *.* TO `installer`@`localhost`;

FLUSH PRIVILEGES;
