<?php

use PhpCsFixer\Fixer\FixerInterface;
use CapsulesCodes\PhpCsFixerCustomFixers\Fixers;
use Symfony\Component\Finder\Finder;


function fixerNamesFromCollection() : array
{
    return array_map( static fn( FixerInterface $fixer ) : string => $fixer->getName(), iterator_to_array( new Fixers() ) );
}




it( 'sorts collection by name', function() : void
{
    $fixerNames = fixerNamesFromCollection();

    $sortedFixerNames = $fixerNames;

    sort( $sortedFixerNames );

    expect( $fixerNames )->toEqual( $sortedFixerNames );
});


it( 'sets fixer in collection', function() : void
{
    $fixerNames = fixerNamesFromCollection();

    $finder = Finder::create()->files()->in( __DIR__ . '/../../src/Fixers/' )->notName( 'Abstract*Fixer.php' );

    foreach( $finder as $file )
    {
        $className = 'CapsulesCodes\\PhpCsFixerCustomFixers\\Fixers\\' . $file->getBasename( '.php' );

        $fixer = new $className();

        expect( $fixer )->toBeInstanceOf( FixerInterface::class );

        expect( $fixerNames )->toContain( $fixer->getName() );
    }
} );
