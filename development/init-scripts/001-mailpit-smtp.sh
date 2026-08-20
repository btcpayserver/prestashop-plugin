#!/bin/sh
# Configure PrestaShop to send mail via Mailpit (SMTP, no auth/TLS).
set -eu

echo "* Configuring PrestaShop SMTP for Mailpit..."

mysql -h"${MYSQL_HOST:-mysql}" -P"${MYSQL_PORT:-3306}" -u"${MYSQL_USER:-prestashop}" -p"${MYSQL_PASSWORD:-prestashop}" "${MYSQL_DATABASE:-prestashop}" <<'SQL'
UPDATE `ps_configuration` SET `value` = '2' WHERE `name` = 'PS_MAIL_METHOD';
UPDATE `ps_configuration` SET `value` = 'mailpit' WHERE `name` = 'PS_MAIL_SERVER';
UPDATE `ps_configuration` SET `value` = '1025' WHERE `name` = 'PS_MAIL_SMTP_PORT';
UPDATE `ps_configuration` SET `value` = 'off' WHERE `name` = 'PS_MAIL_SMTP_ENCRYPTION';
UPDATE `ps_configuration` SET `value` = '' WHERE `name` = 'PS_MAIL_USER';
UPDATE `ps_configuration` SET `value` = '' WHERE `name` = 'PS_MAIL_PASSWD';
SQL

echo "* Mailpit SMTP configured (mailpit:1025)"
