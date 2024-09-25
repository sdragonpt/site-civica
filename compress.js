const express = require('express');
const multer = require('multer');
const Jimp = require('jimp');
const path = require('path');
const app = express();
const PORT = 3000;

// Configuração do multer para upload de arquivos
const storage = multer.memoryStorage();
const upload = multer({ storage: storage });

app.post('/compress', upload.array('imagens'), async (req, res) => {
    try {
        const compressedImages = [];
        for (const file of req.files) {
            const image = await Jimp.read(file.buffer);
            image.quality(60); // Ajuste a qualidade (0-100)
            const compressedBuffer = await image.getBufferAsync(Jimp.MIME_JPEG);
            compressedImages.push({
                originalName: file.originalname,
                buffer: compressedBuffer,
            });
        }
        
        // Aqui você pode salvar os arquivos em seu sistema de arquivos ou retornar os buffers
        // Para este exemplo, vamos retornar o tamanho dos arquivos
        const responseSizes = compressedImages.map(img => ({
            name: img.originalName,
            size: img.buffer.length,
        }));

        res.json(responseSizes);
    } catch (error) {
        console.error(error);
        res.status(500).send('Erro ao processar as imagens.');
    }
});

app.listen(PORT, () => {
    console.log(`Servidor rodando na porta ${PORT}`);
});
