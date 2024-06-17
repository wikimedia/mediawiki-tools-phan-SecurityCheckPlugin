<?php

namespace MediaWiki\HTMLForm\Field;

class HTMLFormField {
}

class HTMLInfoField extends HTMLFormField {
}

class HTMLCheckField extends HTMLFormField {
}

class HTMLMultiSelectField extends HTMLFormField {
}

class HTMLRadioField extends HTMLFormField {
}

class SomeOtherClass {
}

class_alias( HTMLFormField::class, 'HTMLFormField' );
class_alias( HTMLInfoField::class, 'HTMLInfoField' );
class_alias( HTMLCheckField::class, 'HTMLCheckField' );
class_alias( HTMLMultiSelectField::class, 'HTMLMultiSelectField' );
class_alias( HTMLRadioField::class, 'HTMLRadioField' );
class_alias( SomeOtherClass::class, 'SomeOtherClass' );
