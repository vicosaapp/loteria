package com.loteria.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.loteria.app.api.ApiClient;

import org.json.JSONObject;

public class LoginActivity extends AppCompatActivity {
    private EditText emailInput;
    private EditText senhaInput;
    private Button loginButton;
    private ProgressBar progressBar;
    private ApiClient apiClient;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);
        
        // Inicializar ApiClient
        apiClient = ApiClient.getInstance(this);
        
        // Se já estiver logado, redirecionar para o dashboard
        if (apiClient.isLoggedIn()) {
            startActivity(new Intent(this, DashboardActivity.class));
            finish();
            return;
        }
        
        // Inicializar views
        emailInput = findViewById(R.id.emailInput);
        senhaInput = findViewById(R.id.senhaInput);
        loginButton = findViewById(R.id.loginButton);
        progressBar = findViewById(R.id.progressBar);
        
        // Configurar click do botão
        loginButton.setOnClickListener(v -> realizarLogin());
    }
    
    private void realizarLogin() {
        String email = emailInput.getText().toString().trim();
        String senha = senhaInput.getText().toString().trim();
        
        // Validar campos
        if (email.isEmpty() || senha.isEmpty()) {
            Toast.makeText(this, "Preencha todos os campos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        // Mostrar loading
        setLoading(true);
        
        // Fazer requisição de login
        apiClient.login(email, senha,
                response -> {
                    try {
                        // Verificar sucesso
                        if (response.getBoolean("success")) {
                            // Redirecionar para o dashboard
                            startActivity(new Intent(this, DashboardActivity.class));
                            finish();
                        } else {
                            // Mostrar erro
                            String message = response.getString("error");
                            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
                            setLoading(false);
                        }
                    } catch (Exception e) {
                        e.printStackTrace();
                        Toast.makeText(this, "Erro ao processar resposta", Toast.LENGTH_LONG).show();
                        setLoading(false);
                    }
                },
                error -> {
                    // Tratar erro de rede
                    String message = "Erro de conexão";
                    if (error.networkResponse != null) {
                        if (error.networkResponse.statusCode == 401) {
                            message = "Email ou senha inválidos";
                        } else if (error.networkResponse.statusCode == 403) {
                            message = "Conta bloqueada ou inativa";
                        }
                    }
                    Toast.makeText(this, message, Toast.LENGTH_LONG).show();
                    setLoading(false);
                });
    }
    
    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        loginButton.setEnabled(!loading);
        emailInput.setEnabled(!loading);
        senhaInput.setEnabled(!loading);
    }
} 