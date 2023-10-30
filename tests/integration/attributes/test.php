<?php

// Make sure that the plugin doesn't crash when encountering attributes

class MyTestAttribute1 {
}
class MyTestAttribute2 {
}

#[MyTestAttribute1]
class TestClassWithAttribute1 {
}

#[MyTestAttribute1,MyTestAttribute2]
class TestClassWithAttribute2 {
}

