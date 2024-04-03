#!/bin/bash

from_mail="$EXPORT_FROM"
to_mail="$EXPORT_TO"
file_name="auto-export.xls"
subject="Auto Exporter $(date +%F)"
text="A new auto-export has been generated."

cd /app

php export.php > $file_name
echo "$(uuencode $file_name $file_name) $text" | mail -s "$subject" -r "$from_mail" "$to_mail"
rm -f $file_name


