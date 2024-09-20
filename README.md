# NAAD Connector
A PHP client for connecting the National Alert Aggregation & Dissemination (NAAD) System via TCP socket to a REST API.

## Usage

### Local

#### Docker deployment
To build and run via Docker:
```sh
docker build -t bcgovgdx/naad-app .
docker run --rm  bcgovgdx/naad-app
```

#### Docker Desktop Kubernetes deployment
To build and run in Kubernetes via Docker Desktop:
Note: Kubernetes must be enabled in Docker Desktop.
```sh
kubectl config use-context docker-desktop
docker build -t bcgovgdx/naad-app .
kubectl apply -k deployments/kustomize/base/
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