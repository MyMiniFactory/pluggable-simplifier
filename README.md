# File Simplifier

[![Docker Cloud Build Status](https://img.shields.io/docker/cloud/build/myminifactory/pluggable-simplifier)](https://hub.docker.com/r/myminifactory/pluggable-simplifier)

File Simplifier is a docker container to simplify file and optionnaly convert them to ply.
It uses :
- [OpenCTM](http://openctm.sourceforge.net/?page=about) to convert the 3D file to stl and optionnaly convert the stl to ply.
- [Fast-Quadric-Mesh-Simplification](https://github.com/MyMiniFactory/Fast-Quadric-Mesh-Simplification) to simplify the file.

## Build the container
```shell
docker build -t tag_name -f dockerfile_path path_to_the_folder_containing_the_docker_file
```
The -f tag is optional on LINUX if the `Dockerfile` is properly named (no file extension).

## Run the container
```shell
docker run image_name -f|--filename name_of_the_file_without_extension -i|--input input_dir -o|--output output_dir -s|--status path_to_the_folder_where_to_write_the_status_json_file | --targetsize target-size-of-output -t | --convert -c
```

|                         parameter                        |                  example values               |
|----------------------------------------------------------|:---------------------------------------------:|
|           `name_of_the_file_without_extension`           |                    my3dfile                   |
|                        `input_dir`                       |                /app/files/input/              |
|                        `output_dir`                      |                /app/files/output/             |
| `path_to_the_folder_where_to_write_the_status_json_file` |               /app/files/output/              | 
| `target-size-of-output`                                  |                        5                      | 

Parameters :
- `name_of_the_file_without_extension` : the name of the 3D file without the extension.
- `input_dir`: the relative or absolute path to the folder which contains the 3D file.
- `output_dir`: the relative or absolute path to the folder where the outputs folder will be stacked. Note that the path must exist. 
- `path_to_the_folder_where_to_write_the_status_json_file`: the relative or absolute path to the folder which will contain the status.json file which consists in a live report of the process to use with the task actionner.
- `target-size-of-output`: target size in megabytes.
- `convert` is an optional parameter. It toggle the conversion of the output file to PLY.
