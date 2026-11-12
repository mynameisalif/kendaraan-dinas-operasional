<?php
  $host='localhost';
  $db='db_kendaraan_dinas_operasional';
  $user= 'host';
  $pass='';

  $pdo = new PDO("mysql:host=localhost;dbname=db_kendaraan_dinas_operasional","root","");
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  
