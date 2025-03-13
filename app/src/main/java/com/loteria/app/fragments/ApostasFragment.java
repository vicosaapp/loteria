package com.loteria.app.fragments;

import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.loteria.app.R;
import com.loteria.app.api.ApiClient;
import com.loteria.app.utils.Constants;

import org.json.JSONObject;

public class ApostasFragment extends Fragment {
    private static final String TAG = "ApostasFragment";
    
    private View loadingView;
    private View errorView;
    private RecyclerView recyclerView;
    private SwipeRefreshLayout swipeRefresh;
    private boolean isFragmentActive = false;
    
    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        isFragmentActive = true;
    }
    
    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_apostas, container, false);
        
        loadingView = view.findViewById(R.id.loadingView);
        errorView = view.findViewById(R.id.errorView);
        recyclerView = view.findViewById(R.id.recyclerView);
        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        
        swipeRefresh.setOnRefreshListener(this::carregarApostas);
        
        return view;
    }
    
    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        carregarApostas();
    }
    
    @Override
    public void onDestroyView() {
        super.onDestroyView();
        isFragmentActive = false;
    }
    
    private void carregarApostas() {
        if (!isFragmentActive) return;
        
        mostrarLoading();
        
        ApiClient.getInstance(requireContext()).getApostas(
            response -> {
                if (!isFragmentActive) return;
                
                try {
                    ocultarLoading();
                    // Processar dados das apostas
                    // TODO: Implementar adapter e popular recyclerView
                } catch (Exception e) {
                    Log.e(TAG, "Erro ao processar resposta", e);
                    mostrarErro(getString(R.string.erro_resposta));
                }
            },
            error -> {
                if (!isFragmentActive) return;
                
                Log.e(TAG, "Erro ao carregar apostas", error);
                ocultarLoading();
                
                String mensagem = getString(R.string.erro_conexao);
                if (error.networkResponse != null && error.networkResponse.statusCode == 401) {
                    mensagem = getString(R.string.erro_sessao_expirada);
                    if (getActivity() != null) {
                        // Redirecionar para login
                        ApiClient.getInstance(requireContext()).clearToken();
                        // TODO: Implementar redirecionamento para login
                        return;
                    }
                }
                mostrarErro(mensagem);
            }
        );
    }
    
    private void mostrarLoading() {
        if (!isFragmentActive) return;
        
        if (loadingView != null) loadingView.setVisibility(View.VISIBLE);
        if (errorView != null) errorView.setVisibility(View.GONE);
        if (swipeRefresh != null) swipeRefresh.setRefreshing(false);
    }
    
    private void ocultarLoading() {
        if (!isFragmentActive) return;
        
        if (loadingView != null) loadingView.setVisibility(View.GONE);
        if (swipeRefresh != null) swipeRefresh.setRefreshing(false);
    }
    
    private void mostrarErro(String mensagem) {
        if (!isFragmentActive) return;
        
        if (getContext() == null) return;
        
        if (errorView != null) {
            errorView.setVisibility(View.VISIBLE);
            TextView errorText = errorView.findViewById(R.id.errorText);
            if (errorText != null) {
                errorText.setText(mensagem);
            }
        }
        
        Toast.makeText(getContext(), mensagem, Toast.LENGTH_SHORT).show();
    }
} 