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
oc apply -k deployments/kustomize/image-builds --namespace=12345-tools
oc start-build naad-app --follow --namespace=12345-tools
```

### OpenShift Deploy
```sh
oc project 12345-tools
oc apply -k deployments/kustomize/base --namespace=12345-tools
```