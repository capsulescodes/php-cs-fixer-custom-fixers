<?php

if( $foo )
{
	echo $foo;
}
elseif( $bar )
{
	echo $bar;
}

while( $baz )
{
	$baz--;
}

for( $i = 0; $i < 10; $i++ )
{
	echo $i;
}

foreach( $items as $item )
{
	echo $item;
}

switch( $foo )
{
	case 'bar':
		break;
}

$result = match( $foo )
{
	'bar' => 'baz',
};

try
{
	$foo();
}
catch( Exception $exception )
{
	echo $exception;
}

function foo( $bar )
{
	return $bar;
}

$closure = function( $bar )
{
	return $bar;
};

$arrow = fn( $bar ) => $bar;

if( $already )
{
	echo $already;
}
