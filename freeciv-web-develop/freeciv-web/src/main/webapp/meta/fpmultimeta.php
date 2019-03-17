<?php

/* do we want debug output to stderr?
 * This is very heavy so never leave it on in production
 */
$debug=0;

// include the php-code finder
ini_set("include_path", ini_get("include_path") . ":" . $_SERVER["DOCUMENT_ROOT"]);

include_once("php_code/settings.php");

if ($error_msg != NULL) {
  $config_problem = true;
}

if (!isset($config_problem)) {
  include_once("php_code/php_code_find.php");
  // includes for support routines
  include_once(php_code_find("fcdb.php"));
  include_once(php_code_find("versions_file.php"));
  include_once(php_code_find("img.php"));
  include_once(php_code_find("html.php"));

  fcdb_metaserver_connect();
}

?> 
 <h3>Multiplayer Games</h3>

<?php
      $stmt="(select host,port,version,patches,state,message,unix_timestamp()-unix_timestamp(stamp), (select value from variables where name = 'turn' and hostport = CONCAT(s.host ,':',s.port)) as turn from servers s where message not like '%Private%' and type = 'multiplayer' and state = 'Running' order by state desc) UNION (select host,port,version,patches,state,message,unix_timestamp()-unix_timestamp(stamp), (select value from variables where message not like '%Private%' and name = 'turn' and hostport = CONCAT(s.host ,':',s.port)) as turn from servers s where message not like '%Private%' and type = 'multiplayer' and state = 'Pregame' and CONCAT(s.host ,':',s.port) in (select hostport from players where type <> 'A.I.') limit 1) UNION (select host,port,version,patches,state,message,unix_timestamp()-unix_timestamp(stamp), (select value from variables where name = 'turn' and hostport = CONCAT(s.host ,':',s.port)) as turn from servers s where type = 'multiplayer' and state = 'Pregame' limit 2)";
      $res = fcdb_exec($stmt);
      $nr = fcdb_num_rows($res);
      if ( $nr > 0 ) {
	print "<table class='metatable multiplayer'>\n";
        print "<tr class='meta_header'><th>Game Action:</th>";
        print "<th>State</th><th>Players</th>";
        print "<th style='width:45%;'>Message</th>";
        print "<th>Turn:</th></tr>";

        for ( $inx = 0; $inx < $nr; $inx++ ) {
 	  $row = fcdb_fetch_next_row($res, $inx);
	  $mystate = db2html($row["state"]);

          $stmt="select * from players where type='Human' and hostport=\"".$row['host'].":".$row['port']."\"";
	  $res1 = fcdb_exec($stmt);

	  print "<tr class='meta_row ";
	  if (strpos($row["message"],'password-protected') !== false) {  
            print " private_game ";
          } else if ($mystate == "Running") {
            print " running_game ";
	  } else if (fcdb_num_rows($res1) != 0) {
	    print " pregame_with_players ";
	  }
	  print "'><td> "; 

          if ($mystate != "Running") {
           print "<a  class='btn btn-info' href=\"/webclient?action=multi&civserverport=" . db2html($row["port"]) . "&amp;civserverhost=" . db2html($row["host"]) . "&amp;multi=true\">";
           print "Play";
	   print "</a>";
	  } else {
	   print "<a  class='btn btn-info' href=\"/webclient?action=observe&amp;civserverport=" . db2html($row["port"]) . "&amp;civserverhost=" . db2html($row["host"]) . "&amp;multi=true\">";
           print "Observe";
           print "</a>";
	  }


          print "<a class='btn btn-info' href=\"/meta/metaserver.php?server_port=" . db2html($row["host"]) . ":" . db2html($row["port"]) . "\">";
	  	  print "Info";
          print "</a>";

	  print "</td>";
	  print "<td>";

          print db2html($row["state"]);
          print "</td>";
	  if (fcdb_num_rows($res1) == 0) {
		  print ("<td>None" );
	  } else if (fcdb_num_rows($res1) == 1) {
		  print ("<td>" . fcdb_num_rows($res1 ) . " player");
	  } else {
		  print ("<td>" . fcdb_num_rows($res1 ) . " players");
	  }
          print "</td><td style=\"width: 30%\" >";
          print db2html($row["message"]);
	  print "</td><td>";
          print db2html($row["turn"]);
	  print "</td></tr>\n";


        }
        print "</table>  ";
      } else {
        print "<h2>No servers currently listed</h2>";
      }

?>

