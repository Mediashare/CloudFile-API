# CloudFile-API
Server file system with simple API.
[Documentation](https://github.com/Mediashare/CloudFile-API.wiki.git)
## Getting Start
### Installation
```bash
git clone https://github.com/Mediashare/CloudFile-API
cd CloudFile
composer install
bin/console doctrine:schema:update --force
php -S localhost:8000 -t public/
```
### Api endpoint
* ``/`` List file(s)
* ``/upload`` Upload file(s)
* ``/info/{id}`` File informations
* ``/show/{id}`` Show file
* ``/download/{id}`` Download file
* ``/remove/{id}`` Remove file
### Usages
#### Use curl command line tool
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "file2=@/home/user1/Desktop/image2.jpg" \
  localhost:8000/upload
```
#### Use ApiKey for private cloud
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -H "ApiKey: xxxxxxx" \
  localhost:8000/upload
```
#### Add metadata to file(s)
You can add metadata to file(s) with GET & POST methods.
```bash
curl \
  -F "file=@/home/user1/Desktop/image1.jpg" \
  -F "category=image" \
  localhost:8000/upload?foo=bar
```