#!/bin/bash

# show versions
echo "---"
echo "UBUNTU version:"
lsb_release -d

echo "---"
echo "PYTHON version:"
python -V

echo "---"
echo "APACHE version:"
apache2 -v

echo "---"
echo "MYSQL version:"
mysql -V

echo "---"
echo "PHP version:"
php -v

echo "---"
