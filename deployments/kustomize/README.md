# Deployments
The OpenShift deployment for Early Earthquake Warning System (NaadConnector) which uses [kustomize](https://kubectl.docs.kubernetes.io/guides/introduction/kustomize/), and all commands are used from the root of this repository.



## Getting Started for Developers
- Under the `deployments/kustomize` create a folder called `custom`, this folder is ignored by the github repository, and a developer can create a sample test deployment
- create a `deployments/kustomize/custom/kustomization.yaml` and add any of the sample `kustomization.yaml` and update the namespace where you are deploying the application/images/pipelines
- create a `deployments/kustomize/custom/patch.yaml` and add the example patch, which adds 2 naad-sockets (defaults to only 1)
- using the oc command to deploy to openshift
  - login to OpenShift in your terminal
  - run `oc apply -k ./deployments/kustomize/custom/` to deploy to dev
  - run `oc delete -k ./deployments/kustomize/custom/` to remove the deployment to dev

## Application ENV

```bash
# Config

# default values for nad url and repo url, however the app will have logic to overwrite this info.
NAAD_URL=streaming1.naad-adna.pelmorex.com # will become deprecated
NAAD_REPO_URL=capcp1.naad-adna.pelmorex.com # will become deprecated 
# The path where the logs get stored, each log instance will create a subpath and the corresponding log files. /logs/socket-1/app.log, /logs/socket-2/app.log, and /logs/database/app.log
LOG_PATH=/logs
# debug, info, notice, warning (prod), error, critical, alert, emergency
LOG_LEVEL=info
# 0 - unlimited retention, > 0 number of days
LOG_RETENTION_DAYS=365
ALERTS_TO_KEEP=100

# Secrets
# Destination password for the WordPress user (application password)
DESTINATION_PASSWORD='AAAA AAAA AAAA AAAA'
# Destination WordPress site
DESTINATION_URL="http://local:38080/embc/wp-json/naad/v1/alert"
# Destination WordPress user
DESTINATION_USER=naadbot
```

## Database ENV

```bash
# Configs

# the database name
MARIADB_DATABASE="naad_connector"
MARIADB_SERVICE_HOST="mariadb"
MARIADB_SERVICE_PORT="3306"

# Secrets
MARIADB_ROOT_PASSWORD="rootpassword"
```

## Deployment of Image / BuildConfig to OpenShift
### The kustomization.yaml
```yaml
# kustomization.yaml
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization
resources:
- https://github.com/bcgov/naad-connector//deployments/kustomize/image-builds

# Update namespace
namespace: 12345-tools
```

## Deployment of Pipelines to OpenShift
### The kustomization.yaml
```yaml
# kustomization.yaml
apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization
resources:
- https://github.com/bcgov/naad-connector//deployments/kustomize/pipelines

# Update namespace
namespace: 12345-tools
```

## Deployment of Application to OpenShift
### The kustomization.yaml
```yaml
# kustomization.yaml

apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization

resources:
- https://github.com/bcgov/naad-connector//deployments/kustomize/overlays/openshift

# Update namespace
namespace: 12345-dev

# update image namespace and tag
images:
- name: bcgovgdx/naad-app
  newName: image-registry.openshift-image-registry.svc:5000/12345-tools/naad-app
  newTag: latest

patches:
- path: patch.yaml
```

### The patch.yaml
```yaml
# patch.yaml
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: naad-socket
  labels:
spec:
  replicas: 2
```