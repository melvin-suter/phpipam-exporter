#!/bin/bash

from_mail="$EXPORT_FROM"
to_mail="$EXPORT_TO"
file_name="auto-export.xls"
subject="Auto Exporter $(date +%F)"
text="A new auto-export has been generated."

cd /app

php73 export.php > $file_name
echo "$text" | mailx -s "$subject" -a "$file_name" -r "$from_mail" "$to_mail"
rm -f $file_name
