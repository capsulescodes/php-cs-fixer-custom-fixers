<?php

use CapsulesCodes\Fixers\SpacesInsideSquareBracesFixer;


it( "doesn't add spaces inside square braces", function() : void
{
    $fix = $this->fix( 'tests/stubs/before/spaces-inside-square-braces.php', SpacesInsideSquareBracesFixer::class );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/spaces-inside-square-braces/default.php' ) );
} );


it( "adds spaces inside square braces", function() : void
{
    $rules = [ 'CapsulesCodes/spaces_inside_square_braces' => [ 'space' => 'single' ] ];

    $fix = $this->fix( 'tests/stubs/before/spaces-inside-square-braces.php', SpacesInsideSquareBracesFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/spaces-inside-square-braces/single.php' ) );
} );
