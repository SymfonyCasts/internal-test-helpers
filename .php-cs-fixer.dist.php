<?php

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src'])
;

return (new PhpCsFixer\Config())
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => <<<EOF
----------------------------------------------------------------------------
You shouldn't use this package. We do NOT provide support or BC Guarantees.
We will break things between releases, pull requests, commits, and/or merges. 
----------------------------------------------------------------------------

This file is part of the SymfonyCasts Internal Test Helpers package.
Copyright (c) SymfonyCasts <https://symfonycasts.com/>
For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ],
        'no_superfluous_phpdoc_tags' => false,
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;

