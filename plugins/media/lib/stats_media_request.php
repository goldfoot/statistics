<?php


/**
 * Main class to handle saving of media requests
 * 
 * @author Andreas Lenhardt
 */
class stats_media_request
{

    // media urls, for media detection
    const MEDIA_URLS = [
        '/media',
        '/index.php?rex_media_type',
        '/index.php?rex_media_file',
    ];

    const MEDIA_TYPES = [
        '.jpg',
        '.jpeg',
        '.png',
        '.gif',
        '.webp',
        '.tiff',
        '.tif',
        '.ico',
        '.svg',
        '.wbmp',
        '.bmp',
        '.pdf',
        '.doc',
        '.xls',
        '.ppt',
        '.xla',
        '.pps',
        '.ppz',
        '.pot',
        '.dot',
        '.dotx',
        '.docx',
        '.xlsx',
        '.pptx',
        '.odt',
        '.ods',
        '.odp',
        '.odc',
        '.odf',
        '.odi',
        '.odm',
        '.mp3',
        '.mp4',
        '.avi',
        '.mpg',
        '.flv',
        '.ogg',
        '.ogv',
        '.swf',
        '.wmv',
        '.webm',
        '.mpeg',
        '.mov',
        '.qt',
        '.wav',
        '.3gp',
        '.js',
        '.css',
        '.gz',
        '.zip',
        '.rar',
        '.tar',
        '.gzip',
        '.json',
        '.xml',
        '.txt',
        '.csv',
    ];

    private $url;
    private $datetime_now;


    
    /**
     * 
     * 
     * @param string $url 
     * @return void 
     * @author Andreas Lenhardt
     */
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->datetime_now = new DateTime();
    }


    
    /**
     * 
     * 
     * @return bool 
     * @author Andreas Lenhardt
     */
    public function is_media()
    {
        foreach (self::MEDIA_URLS as $el) {
            if (str_starts_with($this->url, $el)) {
                return true;
            }
        }

        foreach (self::MEDIA_TYPES as $el) {
            if (str_ends_with($this->url, $el) || str_ends_with($this->url, strtoupper($el))) {
                return true;
            }
        }

        return false;
    }


    
    /**
     * 
     * 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws rex_sql_exception 
     * @author Andreas Lenhardt
     */
    public function save_media()
    {
        $sql = rex_sql::factory();
        $result = $sql->setQuery('UPDATE ' . rex::getTable('pagestats_media') . ' SET count = count + 1 WHERE url = :url AND date = :date', ['url' => $this->url, 'date' => $this->datetime_now->format('Y-m-d')]);

        if ($result->getRows() === 0) {
            $bot = rex_sql::factory();
            $bot->setTable(rex::getTable('pagestats_media'));
            $bot->setValue('url', $this->url);
            $bot->setValue('date', $this->datetime_now->format('Y-m-d'));
            $bot->setValue('count', 1);
            $bot->insert();
        }
    }
}
