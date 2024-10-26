<?php declare( strict_types = 1 );

namespace CapsulesCodes\PhpCsFixerCustomFixers;

use IteratorAggregate;
use Generator;
use DirectoryIterator;
use PhpCsFixer\Fixer\FixerInterface;


final class Fixers implements IteratorAggregate
{
    public function getIterator() : Generator
    {
        $classNames = [];

        foreach( new DirectoryIterator( __DIR__ . '/Fixers' ) as $fileInfo )
        {
            $fileName = $fileInfo->getBasename( '.php' );

            if( in_array( $fileName, [ '.', '..', 'AbstractFixer', 'AbstractTypesFixer' ], true ) ) continue;

            $classNames[] = __NAMESPACE__ . '\\Fixers\\' . $fileName;
        }

        sort( $classNames );

        foreach( $classNames as $className )
        {
            $fixer = new $className();

            assert( $fixer instanceof FixerInterface );

            yield $fixer;
        }
    }
}
