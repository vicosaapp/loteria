package com.loteria.app.api;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;

import com.android.volley.AuthFailureError;
import com.android.volley.DefaultRetryPolicy;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.toolbox.JsonObjectRequest;
import com.android.volley.toolbox.Volley;
import com.loteria.app.utils.Constants;
import com.loteria.app.utils.Config;
import com.loteria.app.utils.SessionManager;

import org.json.JSONObject;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.TimeUnit;

import okhttp3.Interceptor;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import okhttp3.logging.HttpLoggingInterceptor;

public class ApiClient {
    private static final String TAG = "ApiClient";
    
    private static ApiClient instance;
    private RequestQueue requestQueue;
    private Context context;
    private SharedPreferences prefs;
    private final OkHttpClient client;
    
    private ApiClient(Context context) {
        this.context = context.getApplicationContext();
        this.requestQueue = Volley.newRequestQueue(context);
        this.prefs = context.getSharedPreferences(Constants.PREF_NAME, Context.MODE_PRIVATE);
        
        HttpLoggingInterceptor loggingInterceptor = new HttpLoggingInterceptor();
        loggingInterceptor.setLevel(HttpLoggingInterceptor.Level.BODY);
        
        client = new OkHttpClient.Builder()
            .connectTimeout(Config.getApiTimeout(), TimeUnit.MILLISECONDS)
            .readTimeout(Config.getApiTimeout(), TimeUnit.MILLISECONDS)
            .writeTimeout(Config.getApiTimeout(), TimeUnit.MILLISECONDS)
            .addInterceptor(new AuthInterceptor())
            .addInterceptor(new ResponseInterceptor())
            .addInterceptor(loggingInterceptor)
            .build();
    }
    
    public static synchronized ApiClient getInstance(Context context) {
        if (instance == null) {
            instance = new ApiClient(context);
        }
        return instance;
    }
    
    public void login(String email, String senha, Response.Listener<JSONObject> successListener,
                     Response.ErrorListener errorListener) {
        String url = Constants.getApiLogin();
        
        JSONObject params = new JSONObject();
        try {
            params.put("email", email);
            params.put("senha", senha);
        } catch (Exception e) {
            Log.e(TAG, "Erro ao criar parâmetros de login", e);
            errorListener.onErrorResponse(null);
            return;
        }
        
        JsonObjectRequest request = new JsonObjectRequest(Request.Method.POST, url, params,
                response -> {
                    try {
                        String token = response.getString("token");
                        saveToken(token);
                        successListener.onResponse(response);
                    } catch (Exception e) {
                        Log.e(TAG, "Erro ao processar resposta de login", e);
                        errorListener.onErrorResponse(null);
                    }
                },
                error -> {
                    Log.e(TAG, "Erro na requisição de login", error);
                    errorListener.onErrorResponse(error);
                }) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                Map<String, String> headers = new HashMap<>();
                headers.put("Content-Type", "application/json");
                return headers;
            }
        };
        
        configureRequest(request);
        requestQueue.add(request);
    }
    
    public void getDashboard(final VolleyCallback successCallback, final VolleyErrorCallback errorCallback) {
        String url = Constants.getApiDashboard();
        
        Request request = new Request.Builder()
            .url(url)
            .get()
            .build();
            
        Log.d(TAG, "URL da requisição: " + url);
        
        client.newCall(request).enqueue(new okhttp3.Callback() {
            @Override
            public void onResponse(okhttp3.Call call, Response response) throws IOException {
                try {
                    String responseData = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseData);
                    successCallback.onSuccess(jsonResponse);
                } catch (Exception e) {
                    errorCallback.onError(new VolleyError(e));
                }
            }
            
            @Override
            public void onFailure(okhttp3.Call call, IOException e) {
                errorCallback.onError(new VolleyError(e));
            }
        });
    }
    
    public void getApostas(Response.Listener<JSONObject> successListener,
                          Response.ErrorListener errorListener) {
        String url = Constants.getApiApostas();
        
        JsonObjectRequest request = new JsonObjectRequest(Request.Method.GET, url, null,
                successListener,
                error -> {
                    Log.e(TAG, "Erro na requisição de apostas", error);
                    errorListener.onErrorResponse(error);
                }) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                return getAuthHeaders();
            }
        };
        
        configureRequest(request);
        requestQueue.add(request);
    }
    
    private void configureRequest(JsonObjectRequest request) {
        request.setRetryPolicy(new DefaultRetryPolicy(
            Constants.TIMEOUT_MS,
            DefaultRetryPolicy.DEFAULT_MAX_RETRIES,
            DefaultRetryPolicy.DEFAULT_BACKOFF_MULT
        ));
    }
    
    private Map<String, String> getAuthHeaders() {
        Map<String, String> headers = new HashMap<>();
        headers.put("Content-Type", "application/json");
        headers.put("Accept", "application/json");
        
        String token = getToken();
        if (token != null && !token.isEmpty()) {
            headers.put("Authorization", "Bearer " + token);
        }
        
        return headers;
    }
    
    private void saveToken(String token) {
        prefs.edit().putString(Constants.PREF_TOKEN, token).apply();
    }
    
    public String getToken() {
        return prefs.getString(Constants.PREF_TOKEN, null);
    }
    
    public void clearToken() {
        prefs.edit().remove(Constants.PREF_TOKEN).apply();
    }
    
    public boolean isLoggedIn() {
        return getToken() != null;
    }
    
    private class AuthInterceptor implements Interceptor {
        @Override
        public Response intercept(Chain chain) throws IOException {
            Request original = chain.request();
            
            String token = SessionManager.getInstance(context).getToken();
            Log.d(TAG, "Token sendo enviado: " + (token != null ? "██" : "nulo"));
            
            Request.Builder builder = original.newBuilder()
                .header("Accept", "application/json")
                .header("X-Requested-With", "XMLHttpRequest")
                .header("User-Agent", "LoteriaMobile/" + Config.getAppVersion());
                
            if (token != null) {
                builder.header("Authorization", token);
            }
            
            Request request = builder.build();
            Log.d(TAG, "Headers finais: " + request.headers().toString());
            
            return chain.proceed(request);
        }
    }
    
    private class ResponseInterceptor implements Interceptor {
        @Override
        public Response intercept(Chain chain) throws IOException {
            Request request = chain.request();
            Response response;
            
            try {
                response = chain.proceed(request);
            } catch (Exception e) {
                Log.e(TAG, "Erro na requisição: " + e.getMessage());
                throw e;
            }
            
            if (!response.isSuccessful() && response.code() == 401) {
                SessionManager.getInstance(context).logout();
            }
            
            return response;
        }
    }
    
    public interface VolleyCallback {
        void onSuccess(JSONObject response);
    }
    
    public interface VolleyErrorCallback {
        void onError(VolleyError error);
    }
    
    public static class VolleyError extends Exception {
        public VolleyError(Throwable cause) {
            super(cause);
        }
    }
} 