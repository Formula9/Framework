<?php namespace Nine\Library;

/**
 * **Lib is a collection of static utility methods.**
 *
 * _Utilities have been authored or collected from a number of open sources._
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

final class Lib
{
    const HELPERS = __DIR__ . '/helpers.php';

    use Arrays;
    use Strings;
    use Support;
}

