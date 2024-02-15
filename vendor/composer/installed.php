<?php return array(
    'root' => array(
        'name' => 'patt/pattracking',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => '9b67a10cde335446298781c0ab3c22038bf1317b',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.9.0',
            'version' => '1.9.0.0',
            'reference' => 'b93bcf0fa1fccb0b7d176b0967d969691cd74cca',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'patt/pattracking' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '9b67a10cde335446298781c0ab3c22038bf1317b',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
