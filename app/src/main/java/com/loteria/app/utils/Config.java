package com.loteria.app.utils;

import android.content.Context;
import android.content.res.AssetManager;
import android.util.Log;

import java.io.IOException;
import java.io.InputStream;
import java.util.Properties;

public class Config {
    private static final String TAG = "Config";
    private static final String CONFIG_FILE = "config.properties";
    private static Properties properties;
    
    public static void init(Context context) {
        if (properties != null) return;
        
        properties = new Properties();
        try {
            AssetManager assetManager = context.getAssets();
            InputStream inputStream = assetManager.open(CONFIG_FILE);
            properties.load(inputStream);
            inputStream.close();
        } catch (IOException e) {
            Log.e(TAG, "Erro ao carregar configurações", e);
        }
    }
    
    public static String getProperty(String key, String defaultValue) {
        return properties != null ? properties.getProperty(key, defaultValue) : defaultValue;
    }
    
    public static String getApiBaseUrl() {
        return getProperty("api.base_url", "https://loteria.test/revendedor/");
    }
    
    public static int getApiTimeout() {
        try {
            return Integer.parseInt(getProperty("api.timeout", "15000"));
        } catch (NumberFormatException e) {
            return 15000;
        }
    }
    
    public static String getAppVersion() {
        return getProperty("app.version", "1.0");
    }
    
    public static int getAppBuild() {
        try {
            return Integer.parseInt(getProperty("app.build", "1"));
        } catch (NumberFormatException e) {
            return 1;
        }
    }
} 