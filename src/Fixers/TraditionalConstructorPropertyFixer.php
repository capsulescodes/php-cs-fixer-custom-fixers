<?php declare( strict_types = 1 );


namespace CapsulesCodes\PhpCsFixerCustomFixers\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;


final class TraditionalConstructorPropertyFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getName() : string
    {
        return 'CapsulesCodes/traditional_constructor_property';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Constructor promoted properties MUST be converted to traditional property declarations with constructor assignments.', [] );
    }

    public function getPriority() : int
    {
        return 0;
    }

    public function isCandidate( Tokens $tokens ) : bool
    {
        return $tokens->isTokenKindFound( T_CLASS );
    }

    protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
    {
        for( $index = count( $tokens ) - 1; $index >= 0; --$index )
        {
            if( ! $tokens[ $index ]->isGivenKind( T_CLASS ) ) continue;

            $this->fixClass( $tokens, $index );
        }
    }

    private function fixClass( Tokens $tokens, int $classIndex ) : void
    {
        $constructorIndex = $this->findConstructor( $tokens, $classIndex );

        if( $constructorIndex === null ) return;

        $openParen = $tokens->getNextTokenOfKind( $constructorIndex, [ '(' ] );
        $closeParen = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParen );

        $properties = $this->getPromotedProperties( $tokens, $openParen, $closeParen );

        if( empty( $properties ) ) return;

        $openBrace = $tokens->getNextTokenOfKind( $closeParen, [ '{' ] );
        $closeBrace = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_CURLY_BRACE, $openBrace );

        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $indent = $this->whitespacesConfig->getIndent();
        $classIndent = $this->getIndentation( $tokens, $constructorIndex );
        $bodyIndent = $classIndent . $indent;

        $this->insertAssignments( $tokens, $openBrace, $closeBrace, $properties, $bodyIndent, $classIndent, $lineEnding );

        $this->removePromotions( $tokens, $properties );

        $this->insertPropertyDeclarations( $tokens, $constructorIndex, $properties, $classIndent, $lineEnding );
    }

    private function findConstructor( Tokens $tokens, int $classIndex ) : int | null
    {
        $classOpenBrace = $tokens->getNextTokenOfKind( $classIndex, [ '{' ] );
        $classCloseBrace = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpenBrace );

        for( $i = $classOpenBrace + 1; $i < $classCloseBrace; ++$i )
        {
            if( $tokens[ $i ]->isGivenKind( T_CLASS ) )
            {
                $nestedBrace = $tokens->getNextTokenOfKind( $i, [ '{' ] );
                $i = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_CURLY_BRACE, $nestedBrace );

                continue;
            }

            if( ! $tokens[ $i ]->isGivenKind( T_FUNCTION ) ) continue;

            $nextMeaningful = $tokens->getNextMeaningfulToken( $i );

            if( $tokens[ $nextMeaningful ]->getContent() === '__construct' ) return $i;
        }

        return null;
    }

    private function getPromotedProperties( Tokens $tokens, int $openParen, int $closeParen ) : array
    {
        $properties = [];

        for( $i = $openParen + 1; $i < $closeParen; ++$i )
        {
            $promotionKinds = [
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC,
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED,
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE,
            ];

            if( ! $tokens[ $i ]->isGivenKind( $promotionKinds ) ) continue;

            $visibilityMap = [
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC => T_PUBLIC,
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED => T_PROTECTED,
                CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE => T_PRIVATE,
            ];

            $visibilityToken = new Token( [ $visibilityMap[ $tokens[ $i ]->getId() ], $tokens[ $i ]->getContent() ] );
            $removalStart = $i;
            $readonly = false;

            $j = $i + 1;

            if( $tokens[ $j ]->isWhitespace() ) $j++;

            if( $tokens[ $j ]->isGivenKind( T_READONLY ) )
            {
                $readonly = true;
                $j++;

                if( $tokens[ $j ]->isWhitespace() ) $j++;
            }

            $removalEnd = $j - 1;
            $typeStart = $j;

            while( $j < $closeParen && ! $tokens[ $j ]->isGivenKind( T_VARIABLE ) ) $j++;

            $name = $tokens[ $j ]->getContent();

            $typeTokens = [];

            for( $k = $typeStart; $k < $j; ++$k )
            {
                $typeTokens[] = clone $tokens[ $k ];
            }

            while( ! empty( $typeTokens ) && end( $typeTokens )->isWhitespace() )
            {
                array_pop( $typeTokens );
            }

            $properties[] = [
                'visibilityToken' => $visibilityToken,
                'readonly' => $readonly,
                'typeTokens' => $typeTokens,
                'name' => $name,
                'removalStart' => $removalStart,
                'removalEnd' => $removalEnd,
            ];
        }

        return $properties;
    }

    private function removePromotions( Tokens $tokens, array $properties ) : void
    {
        for( $i = count( $properties ) - 1; $i >= 0; --$i )
        {
            for( $j = $properties[ $i ][ 'removalEnd' ]; $j >= $properties[ $i ][ 'removalStart' ]; --$j )
            {
                $tokens->clearAt( $j );
            }
        }
    }

    private function insertAssignments( Tokens $tokens, int $openBrace, int $closeBrace, array $properties, string $bodyIndent, string $classIndent, string $lineEnding ) : void
    {
        $bodyIsEmpty = true;

        for( $i = $openBrace + 1; $i < $closeBrace; ++$i )
        {
            if( ! $tokens[ $i ]->isWhitespace() )
            {
                $bodyIsEmpty = false;

                break;
            }
        }

        $insertTokens = [];

        foreach( $properties as $property )
        {
            $propName = ltrim( $property[ 'name' ], '$' );

            $insertTokens[] = new Token( [ T_WHITESPACE, $lineEnding . $bodyIndent ] );
            $insertTokens[] = new Token( [ T_VARIABLE, '$this' ] );
            $insertTokens[] = new Token( [ T_OBJECT_OPERATOR, '->' ] );
            $insertTokens[] = new Token( [ T_STRING, $propName ] );
            $insertTokens[] = new Token( [ T_WHITESPACE, ' ' ] );
            $insertTokens[] = new Token( '=' );
            $insertTokens[] = new Token( [ T_WHITESPACE, ' ' ] );
            $insertTokens[] = new Token( [ T_VARIABLE, $property[ 'name' ] ] );
            $insertTokens[] = new Token( ';' );
        }

        if( $bodyIsEmpty )
        {
            for( $i = $openBrace + 1; $i < $closeBrace; ++$i )
            {
                if( $tokens[ $i ]->isWhitespace() ) $tokens->clearAt( $i );
            }

            $insertTokens[] = new Token( [ T_WHITESPACE, $lineEnding . $classIndent ] );
        }

        $tokens->insertAt( $openBrace + 1, $insertTokens );
    }

    private function insertPropertyDeclarations( Tokens $tokens, int $constructorIndex, array $properties, string $classIndent, string $lineEnding ) : void
    {
        $constructorVisibility = $tokens->getPrevMeaningfulToken( $constructorIndex );

        $whitespaceBefore = $constructorVisibility;

        for( $i = $constructorVisibility - 1; $i >= 0; --$i )
        {
            if( $tokens[ $i ]->isWhitespace() )
            {
                $whitespaceBefore = $i;

                break;
            }
        }

        $tokens[ $whitespaceBefore ] = new Token( [ T_WHITESPACE, $lineEnding . $lineEnding . $classIndent ] );

        $declarationTokens = [];

        foreach( $properties as $property )
        {
            $declarationTokens[] = new Token( [ T_WHITESPACE, $lineEnding . $classIndent ] );
            $declarationTokens[] = clone $property[ 'visibilityToken' ];

            if( $property[ 'readonly' ] )
            {
                $declarationTokens[] = new Token( [ T_WHITESPACE, ' ' ] );
                $declarationTokens[] = new Token( [ T_READONLY, 'readonly' ] );
            }

            if( ! empty( $property[ 'typeTokens' ] ) )
            {
                $declarationTokens[] = new Token( [ T_WHITESPACE, ' ' ] );

                foreach( $property[ 'typeTokens' ] as $typeToken )
                {
                    $declarationTokens[] = clone $typeToken;
                }
            }

            $declarationTokens[] = new Token( [ T_WHITESPACE, ' ' ] );
            $declarationTokens[] = new Token( [ T_VARIABLE, $property[ 'name' ] ] );
            $declarationTokens[] = new Token( ';' );
        }

        $tokens->insertAt( $whitespaceBefore, $declarationTokens );
    }

    private function getIndentation( Tokens $tokens, int $index ) : string
    {
        for( $i = $index - 1; $i >= 0; --$i )
        {
            if( ! $tokens[ $i ]->isWhitespace() ) continue;

            $content = $tokens[ $i ]->getContent();

            if( str_contains( $content, "\n" ) )
            {
                $lines = explode( "\n", $content );

                return end( $lines );
            }
        }

        return '';
    }
}
