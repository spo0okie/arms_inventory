# Операционные системы → Hardware (отпечаток железа)

JSON-объект с железом, обнаруженным внутри ОС. Заполняется скриптом
инвентаризации; руками менять не нужно — при следующем срабатывании скрипт
перезапишет значение.

Строго говоря, это не совсем валидный JSON: не хватает обертки из квадратных
скобок `[]`, т.к. это массив устройств. В валидном виде выглядит примерно так:

```javascript
[
 {"motherboard":
  {
   "manufacturer":"ASUSTeK COMPUTER INC.",
   "product":"H61M-G",
   "serial":"140222247102869"
  }
 },
 {"processor":
  {
   "model":"Intel(R) Core(TM) i5-3570S CPU @ 3.10GHz",
   "cores":"4"
  }
 },
 {"memorybank":
  {
   "manufacturer":"Hynix/Hyundai",
   "capacity":"8192"
  }
 },
 {"harddisk":
  {
   "model":"WDC WD1003FZEX-00MK2A0",
   "size":"1000"
  }
 },
 {"videocard":
  {
   "name":"AMD Radeon (TM) R9 200 Series",
   "ram":"2048"
  }
 }
]
```
