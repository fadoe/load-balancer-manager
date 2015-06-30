# Marktjagd Load Balancer Manager
---

## About

This console tool manages the Apache2 load balancer for Apache 2.2 and 2.4. The following commands are available:

```
balancermanager show
```

Shows the configured webserver.

```
balancermanager status <loadbalancer>
```

Shows the status from the webservers on load balancer.

```
balancermanager activate <loadbalancer> <webserver>
```

Activates the <webserver> on <loadbalancer>.

```
balancermanager deactivate <loadbalancer> <webserver>
```

Deactivates the <webserver> on <loadbalancer>.

## Installation and configuration

You can install this tool with composer or checkout the git repository.

You need a config file called ```ldm-config.yml``` in ```app/config``` or on other place. Then you must give the file
path to the command with the parameter ```-config-path```.

The config file looks like:

```yml
marktjagd_load_balancer_manager:
    loadbalancer1:                      # the name from the load balancer, used for command line
        host: <http://example.com>      # the url to the load balancer
        part: <portal>                  # the part on the apache page after balancer://
        auth:                           # authentication via http auth, optional
            username: <username>        # the username
            password: <password>        # the password
        hosts:                          # load balancer hosts
            web1:                       # the name for a worker, used for command line
                host: 192.168.1.1       # the host for the worker
            web2:                       # other name for a worker, used for command line
                host: 192.168.1.2       # the host for the worker
            ...                         # more worker
    loadbalancer2:                      # other name from a load balancer, used for command line
        host: <http://example.com>      # other url to the load balancer
        part: <blog>                    # other name on the apache page after balancer://
        auth:
            ...
        hosts:
            ...
```

## Examples

### Show status for loadbalancer1

```
balancermanager status loadbalancer1
```

### Activate web1 on loadbalancer1

```
balancermanager activate loadbalancer1 web1
```

### Deactivate web1 on loadbalancer1

```
balancermanager deactivate loadbalancer1 web1
```

