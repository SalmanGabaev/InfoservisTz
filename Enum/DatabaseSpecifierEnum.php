<?php

namespace FpDbTest\Enum;

enum DatabaseSpecifierEnum: string
{
      case INT = 'd';
      case FLOAT = 'f';
      case ARRAY = 'a';
      case LIST_OR_ID = '#';
      case NULL = 'null';
}
