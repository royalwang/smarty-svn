Test of { include } and local/global variable scope
<br>the original value of $foo = {$foo}
<br>
{assign var=foo2 value='yzx'}
<br> foo2 before { include } = {$foo2}
{include file='test_inc2.tpl'}
<br>
<br>Here we are back in test_inc.tpl
<br>$foo has its old value = {$foo}
<br>this is $foo2 a global variable created in test_inc2.tpl = {$foo2}