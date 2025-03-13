public class DashboardFragment extends Fragment {
    private TextView totalApostasView;
    private TextView valorTotalView;
    private View loadingView;
    private View errorView;
    private boolean isFragmentActive = false;

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        isFragmentActive = true;
    }

    @Override
    public void onDestroy() {
        super.onDestroy();
        isFragmentActive = false;
    }

    private void carregarDados() {
        if (loadingView != null) loadingView.setVisibility(View.VISIBLE);
        if (errorView != null) errorView.setVisibility(View.GONE);

        ApiClient.getInstance(requireContext()).getDashboard(
            response -> {
                if (!isFragmentActive) return;
                
                try {
                    if (loadingView != null) loadingView.setVisibility(View.GONE);
                    
                    JSONObject data = response.getJSONObject("data");
                    JSONArray resumo = data.getJSONArray("resumo_semanal");
                    
                    int totalApostas = 0;
                    double valorTotal = 0;
                    
                    for (int i = 0; i < resumo.length(); i++) {
                        JSONObject dia = resumo.getJSONObject(i);
                        totalApostas += dia.getInt("total_apostas");
                        valorTotal += dia.getDouble("valor_total");
                    }
                    
                    if (totalApostasView != null) {
                        totalApostasView.setText(String.valueOf(totalApostas));
                    }
                    if (valorTotalView != null) {
                        valorTotalView.setText(String.format("R$ %.2f", valorTotal));
                    }
                    
                } catch (Exception e) {
                    Log.e("DashboardFragment", "Erro ao processar resposta", e);
                    mostrarErro(getString(R.string.erro_resposta));
                }
            },
            error -> {
                if (!isFragmentActive) return;
                
                Log.e("DashboardFragment", "Erro ao carregar dashboard", error);
                if (loadingView != null) loadingView.setVisibility(View.GONE);
                
                String mensagem = getString(R.string.erro_conexao);
                if (error.networkResponse != null) {
                    if (error.networkResponse.statusCode == 401) {
                        mensagem = getString(R.string.erro_sessao_expirada);
                        // Redirecionar para login
                        if (getActivity() != null) {
                            SessionManager.getInstance(requireContext()).logout();
                            startActivity(new Intent(getActivity(), LoginActivity.class));
                            getActivity().finish();
                            return;
                        }
                    }
                }
                mostrarErro(mensagem);
            }
        );
    }

    private void mostrarErro(String mensagem) {
        if (!isFragmentActive) return;
        
        if (errorView != null) {
            errorView.setVisibility(View.VISIBLE);
            TextView errorText = errorView.findViewById(R.id.errorText);
            if (errorText != null) {
                errorText.setText(mensagem);
            }
        }
    }
    
    // ... existing code ...
} 