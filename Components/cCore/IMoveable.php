<?php
interface IMoveable {
	
    function AtBeginning();
    function AtEnd();
    function Count();
    function MoveFirst();
    function MoveLast();
    function Clear();
    function MoveNext();
    function MovePrevious();
    function Remove();
		
}
?>