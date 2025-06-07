# Stores de Pinia

## BCV Store

Store para manejar la tasa de cambio del Banco Central de Venezuela (BCV).

### Características

- Se inicializa automáticamente al arrancar la aplicación
- Obtiene los datos de la API `/api/bcv`
- Proporciona estados reactivos para la tasa, fecha, loading y errores
- Incluye método para recargar los datos

### Uso

```javascript
import { useBcvStore } from '@/stores/bcv'
import { storeToRefs } from 'pinia'

// En un componente Vue
const bcvStore = useBcvStore()
const { bcv, date, loading, error } = storeToRefs(bcvStore)

// Para recargar los datos
await bcvStore.$reloadBcvAmount()
```

### Estados disponibles

- `bcv`: Tasa de cambio actual (number | null)
- `date`: Fecha de la tasa (string | null)  
- `loading`: Estado de carga (boolean)
- `error`: Mensaje de error si ocurre alguno (string | null)

### Métodos disponibles

- `$reloadBcvAmount()`: Recarga la tasa BCV desde la API
- `fetchBcvRate()`: Método interno para obtener los datos 
