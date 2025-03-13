package com.loteria.app;

import android.os.Bundle;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import com.loteria.app.base.AuthenticatedActivity;

import org.json.JSONArray;
import org.json.JSONObject;

public class DashboardActivity extends AuthenticatedActivity {
    private TextView totalApostasView;
    private TextView valorTotalView;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);
        
        // Inicializar views
        totalApostasView = findViewById(R.id.totalApostasView);
        valorTotalView = findViewById(R.id.valorTotalView);
        
        // Carregar dados
        carregarDados();
    }
    
    private void carregarDados() {
        // Fazer requisição ao dashboard
        apiClient.getDashboard(
                response -> {
                    try {
                        JSONObject data = response.getJSONObject("data");
                        JSONArray resumo = data.getJSONArray("resumo_semanal");
                        
                        // Calcular totais
                        int totalApostas = 0;
                        double valorTotal = 0;
                        
                        for (int i = 0; i < resumo.length(); i++) {
                            JSONObject dia = resumo.getJSONObject(i);
                            totalApostas += dia.getInt("total_apostas");
                            valorTotal += dia.getDouble("valor_total");
                        }
                        
                        // Atualizar views
                        totalApostasView.setText(String.valueOf(totalApostas));
                        valorTotalView.setText(String.format("R$ %.2f", valorTotal));
                        
                    } catch (Exception e) {
                        e.printStackTrace();
                        Toast.makeText(this, R.string.erro_resposta, Toast.LENGTH_LONG).show();
                    }
                },
                error -> {
                    // Verificar se é erro de autenticação
                    if (error.networkResponse != null && error.networkResponse.statusCode == 401) {
                        handleSessionExpired();
                        return;
                    }
                    
                    // Mostrar erro genérico
                    Toast.makeText(this, R.string.erro_conexao, Toast.LENGTH_LONG).show();
                });
    }
} 