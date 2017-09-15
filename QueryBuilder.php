<?php
namespace SD\QueryBuilder;

class QueryBuilder {
    private const ESCAPES = [
        "\x00" => '\\0',
        "\n"   => '\\n',
        "\r"   => '\\r',
        "'"    => '\\\'',
        "\""   => '\\"',
        "\x1a" => '\\Z',
    ];

    private $lines = [];
    private $values = [];

    /**
     * @param $line string
     * @param $values array
     *  OR
     * @param ...$values array
    **/
    public function addClause($line, $values = []) {
        if (!is_array($values)) {
            $values = array_slice(func_get_args(), 1);
        }
        $this->lines[] = $line;
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Add a clause with IN statement.
     *
     * Supports only one placeholder in the line which will be expanded
     * to parenthesized number of values placeholders of that type.
     *
     * @param $line   string
     * @param $values array
     * @return self
    **/
    public function addMultiClause($line, array $values) {
        $count = count($values);
        $expandedLine = preg_replace_callback(
            '/%[dfs]/',
            function ($matches) use ($count) {
                return '(' . implode(', ', array_fill(0, $count, $matches[0])) . ')';
            },
            $line
        );
        $this->addClause($expandedLine, $values);
        return $this;
    }

    public function getQuery() {
        return $this->prepare(implode("\n", $this->lines), $this->values);
    }

    /**
     * Shamelessly copy-pasted from WordPress wpdb implementation.
     *
     * @param $query string
     * @param $args  array
     * @return string
    **/
    private function prepare($query, ...$args) {
        // If args were passed as an array (as in vsprintf), move them up
        if ( isset( $args[0] ) && is_array($args[0]) ) {
            $args = $args[0];
        }
        $query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
        $query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
        $query = preg_replace( '|(?<!%)%f|' , '%F', $query ); // Force floats to be locale unaware
        $query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
        $args = array_map(function ($arg) { return $this->escape($arg); }, $args);
        return vsprintf($query, $args);
    }

    private function escape($arg) {
        if (is_float($arg)) {
            // Floats are safe anyway.
            return $arg;
        }
        if (!mb_check_encoding($arg, 'UTF-8')) {
            throw new QueryBuilderException('String value is not a valid UTF-8 sequence');
        }
        return strtr($arg, self::ESCAPES);
    }
}

