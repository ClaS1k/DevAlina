<?php
$dbc=mysqli_connect('Your host','login','password','dataBase')
OR die (mysqli_connect_error());
mysqli_set_charset($dbc, 'utf-8');
