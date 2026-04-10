<?php

use CapsulesCodes\PhpCsFixerCustomFixers\Fixers\SingleLineStatementFixer;


it( 'collapses single statement control structures to one line', function() : void
{
    $fix = $this->fix( 'tests/stubs/before/single-line-statement.php', SingleLineStatementFixer::class );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/single-line-statement/default.php' ) );
} );
