package com.panaderia.celeste;

import android.os.Bundle;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);
        // 1. Enlazamos el visor del diseño con el código
        WebView miVisorWeb = findViewById(R.id.wv_panaderia);

// 2. Activamos JavaScript (necesario para tu sitio PHP)
        miVisorWeb.getSettings().setJavaScriptEnabled(true);

// 3. Hacemos que los links se abran dentro de la app y no en Chrome
        miVisorWeb.setWebViewClient(new WebViewClient());

// 4. Cargamos tu URL
        miVisorWeb.loadUrl("https://panaderiaceleste.free.nf/");

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });
    }
}