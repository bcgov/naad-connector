# Deployments
The OpenShift deployment for Early Earthquake System (NAAD) which uses [kustomize](https://kubectl.docs.kubernetes.io/guides/introduction/kustomize/), and all commands are used from the root of this repository.

## Upcoming Changes
This section is to be removed, once all the deployments have been changed, and the application has been updated.

- The `POD_INDEX` env variable is being passed to indicate if it is the primary or secondary (1, being primary, and > 1 being secondary) 
  - application code will have to be updated
- The log path `LOG_PATH` env will be just a path that points to `/var/log/`,
  - each instance of creation of log will define its own path
  - examples are  `/var/log/naad-1`, `/var/log/naad-1` and `/var/log/naad-cleanup`
- The `NAAD_URL` and `NAAD_URL_REPO` will be part of the application, and will be based on the value of POD_INDEX, primary or secondary url.
- all logging env variables will have defaults
- Removing of initContainers, and the application will check the db connection
  - remove `MARIADB_SERVICE_HOST` and `MARIADB_SERVICE_PORT` variables
- Once all application changes have been made
  - remove `base` deployments and replace it with the `base2` deployments
  - remove `overlays/openshift` and replace it with `overlays/openshift2`

## Getting Started
- Under the `deployments/kustomize` create a folder called `custom`, this folder is ignored by the github repository, and a developer can create sample and test deployments
- create a `deployments/kustomize/custom/kustomization.yaml` and add the sample `kustomization.yaml` and update the namespace
- create a `deployments/kustomize/custom/patch.yaml` and add the example patch
- using the oc command to deploy to openshift
  - login to OpenShift in your terminal
  - run `oc apply -k ./deployments/kustomize/custom/` to deploy to dev
  - run `oc delete -k ./deployments/kustomize/custom/` to remove the deployment to dev

## Application

### ENV

```bash
# Config

# default values for nad url and repo url, however the app will have logic to overwrite this info.
NAAD_URL=streaming1.naad-adna.pelmorex.com # will become deprecated
NAAD_REPO_URL=capcp1.naad-adna.pelmorex.com # will become deprecated 
# The path where the logs get stored, each log instance will create a subpath and the corresponding log files.
LOG_PATH=/var/log/app/naad/app.log
# info
LOG_LEVEL=info
# 0 - no retention, > 0 number of days
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

## Database


### ENV
```bash
# Configs

# the database name
MARIADB_DATABASE="naad_connector"
# Temporary, they will get removed once init containers get removed.
MARIADB_SERVICE_HOST="mariadb"
MARIADB_SERVICE_PORT="3306"

# Secrets
MARIADB_ROOT_PASSWORD="rootpassword"
```

## Deployment to OpenShift
### The kustomization.yaml
```yaml
# kustomization.yaml

apiVersion: kustomize.config.k8s.io/v1beta1
kind: Kustomization
resources:
# temporary will remove overlays/openshift and mv openshift2 to overlays/openshift
- ../../overlays/openshift2

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