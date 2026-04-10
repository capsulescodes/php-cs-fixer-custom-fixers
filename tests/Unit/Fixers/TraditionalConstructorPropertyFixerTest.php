<?php

use CapsulesCodes\PhpCsFixerCustomFixers\Fixers\TraditionalConstructorPropertyFixer;


it( 'converts promoted properties to traditional declarations', function() : void
{
    $fix = $this->fix( 'tests/stubs/before/traditional-constructor-property.php', TraditionalConstructorPropertyFixer::class );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/traditional-constructor-property/default.php' ) );
} );
