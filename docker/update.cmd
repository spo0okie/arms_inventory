cd arms
git pull
cd ..
cp prod\index.php arms\web\index.php
del /f /q arms\web\index-test*

call build.cmd
docker push spo0okie/inventory:v1