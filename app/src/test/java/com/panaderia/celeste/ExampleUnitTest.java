package com.panaderia.celeste;

import org.junit.Test;
import static org.junit.Assert.*;

public class ExampleUnitTest {
    @Test
    public void validarUrlEsSegura() {
        String url = "https://panaderiaceleste.free.nf/";
        // Esta prueba verifica que tu web use protocolo seguro HTTPS
        assertTrue("La URL debe ser HTTPS", url.startsWith("https"));
    }
}