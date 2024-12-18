# Usa la imagen base de Node
FROM node:18 AS build

# Crea el directorio de la aplicación
WORKDIR /app

# Copia el package.json y package-lock.json
COPY package*.json ./

# Instala las dependencias
RUN npm install

# Copia el resto de los archivos
COPY . /app/.

# Ejecuta el build
RUN npm run build
