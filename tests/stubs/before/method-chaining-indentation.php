<?php

Foo::bar()->baz()->qux();



Foo
::bar()->baz()->qux();



Foo

::bar()->baz()->qux();



Foo::

bar()->baz()->qux()

->quux()->corge();



Foo::bar()
                ->baz()->qux()->quux()->corge();



Foo::bar()
->baz()->qux()



->quux()->corge();



$this->foo->bar();



$this->foo->bar()->baz()->qux()->quux()->corge();



$this->foo->bar()->baz()->qux( $this->quux()->corge() );



$this->foo->bar()->baz()->qux()->quux( $this->corge() );



$this->foo->bar()->baz()->qux()->quux()->corge( $this->grault() );



$this->foo->bar( $this->baz()->qux()->quux()->quux() );



$this->foo->bar( $this->baz()->qux()->quux()->quux()->corge() );
