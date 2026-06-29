# Check to see if the NGINX_FILE_PROXY env variable exists.
if
  [ -n "${NGINX_FILE_PROXY}" ] && # Check to see if NGINX_FILE_PROXY variable exists.
  [ -f ${DDEV_APPROOT}/.ddev/nginx_full/nginx-site.tmpl ] && # Check to see if template file exists.
  grep -q "#ddev-generated" ${DDEV_APPROOT}/.ddev/nginx_full/nginx-site.conf; then # Check to make sure Nginx file hasn't been modified.
  # Replace Env Variable in Nginx File.
  envsubst '${NGINX_FILE_PROXY}' < ${DDEV_APPROOT}/.ddev/nginx_full/nginx-site.tmpl > ${DDEV_APPROOT}/.ddev/nginx_full/nginx-site.conf
  # Replace the existing nginx site file with the new one.
  cp ${DDEV_APPROOT}/.ddev/nginx_full/nginx-site.conf /etc/nginx/sites-enabled/nginx-site.conf
fi
