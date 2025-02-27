<?php
if (!defined('FUNCTIONS_INCLUDED')) {
    define('FUNCTIONS_INCLUDED', true);

    // Função para formatar o tempo decorrido
    function tempo_decorrido($data_mysql) {
        $data_anterior = strtotime($data_mysql);
        $agora = time();
        $diferenca = $agora - $data_anterior;
        
        $minutos = round($diferenca / 60);
        $horas = round($diferenca / 3600);
        $dias = round($diferenca / 86400);
        $semanas = round($diferenca / 604800);
        $meses = round($diferenca / 2419200);
        $anos = round($diferenca / 29030400);
        
        if ($diferenca < 60) {
            return "Agora mesmo";
        } elseif ($minutos < 60) {
            return $minutos . " minuto" . ($minutos > 1 ? "s" : "") . " atrás";
        } elseif ($horas < 24) {
            return $horas . " hora" . ($horas > 1 ? "s" : "") . " atrás";
        } elseif ($dias < 7) {
            return $dias . " dia" . ($dias > 1 ? "s" : "") . " atrás";
        } elseif ($semanas < 4) {
            return $semanas . " semana" . ($semanas > 1 ? "s" : "") . " atrás";
        } elseif ($meses < 12) {
            return $meses . " mês" . ($meses > 1 ? "es" : "") . " atrás";
        } else {
            return $anos . " ano" . ($anos > 1 ? "s" : "") . " atrás";
        }
    }
}
?> 