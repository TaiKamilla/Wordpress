#!/bin/bash
# Import the SQL dump with MySQL 8+ collations replaced for MariaDB compatibility
sed 's/utf8mb4_0900_ai_ci/utf8mb4_general_ci/g' /docker-entrypoint-initdb.d/1752080044_journalismtrustinitiative_org.sql.bak | mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
