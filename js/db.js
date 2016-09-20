var db = openDatabase ("estudantedfmobile", "1.0", "Estudante DF Mobile", 65535);
db.transaction (function (transaction) 
{
	console.log('Configurando Banco de Dados...');

	//var sql = "DROP TABLE config";
	//transaction.executeSql (sql, undefined, function() { }, error);
	
	var sql = "CREATE TABLE IF NOT EXISTS config " +
		" (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, " +
		"url_servidor VARCHAR(200), " +
		"id_pessoa INTEGER, " +
		"nome VARCHAR(100), " + 
		"grupo VARCHAR(100) " + 
		")"
	transaction.executeSql (sql, undefined, function() { }, error);
	//console.log(sql);
	
	var sql = "INSERT OR IGNORE INTO config (id, url_servidor) VALUES ('1', 'http://192.168.100.5/idestudantilmobile/sincronizar.php') ";
	transaction.executeSql (sql, undefined, function() { }, error);
	//console.log(sql);
});

function error (transaction, err) 
{
	console.log("Erro no banco de dados: " + err.message);
	return false;
}