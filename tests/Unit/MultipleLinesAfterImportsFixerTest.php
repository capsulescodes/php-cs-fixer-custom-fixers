<?php

use CapsulesCodes\Fixers\MultipleLinesAfterImportsFixer;


it( "adds multiple lines after imports", function() : void
{
    $fix = $this->fix( 'tests/stubs/before/multiple-lines-after-imports.php', MultipleLinesAfterImportsFixer::class );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/multiple-lines-after-imports/default.php' ) );
} );


it( "adds zero lines after imports", function() : void
{
    $rules = [ 'CapsulesCodes/multiple_lines_after_imports' => [ 'lines' => 0 ] ];

    $fix = $this->fix( 'tests/stubs/before/multiple-lines-after-imports.php', MultipleLinesAfterImportsFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/multiple-lines-after-imports/lines-zero.php' ) );
} );


it( "adds four lines after imports", function() : void
{
    $rules = [ 'CapsulesCodes/multiple_lines_after_imports' => [ 'lines' => 4 ] ];

    $fix = $this->fix( 'tests/stubs/before/multiple-lines-after-imports.php', MultipleLinesAfterImportsFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/multiple-lines-after-imports/lines-four.php' ) );
} );
