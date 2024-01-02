# NAAD Connector
A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local
To build and run via Docker:
```sh
docker build -t naad-app .
docker run --rm  naad-app
```

### OpenShift Build
```sh
# Change nameplate 
oc project 12345-tools
oc apply  -k deployments/kustomize/image-builds -n 12345-tools
oc start-build naad-app --follow -n 12345-tools
```