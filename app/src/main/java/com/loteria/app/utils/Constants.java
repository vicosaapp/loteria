package com.loteria.app.utils;

public class Constants {
    // Preferências
    public static final String PREF_NAME = "LoteriaPref";
    public static final String PREF_TOKEN = "token";
    
    // Timeouts
    public static final int TIMEOUT_MS = 15000;
    
    // API Endpoints
    public static String getApiBaseUrl() {
        return Config.getApiBaseUrl();
    }
    
    public static String getApiLogin() {
        return getApiBaseUrl() + "login.php";
    }
    
    public static String getApiDashboard() {
        return getApiBaseUrl() + "dashboard.php";
    }
    
    public static String getApiApostas() {
        return getApiBaseUrl() + "apostas.php";
    }
} 