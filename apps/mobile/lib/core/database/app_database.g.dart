// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_database.dart';

// ignore_for_file: type=lint
class $OrganizationsCacheTable extends OrganizationsCache
    with TableInfo<$OrganizationsCacheTable, OrganizationsCacheData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $OrganizationsCacheTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [organizationId, name];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'organizations_cache';
  @override
  VerificationContext validateIntegrity(
    Insertable<OrganizationsCacheData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId};
  @override
  OrganizationsCacheData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return OrganizationsCacheData(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
    );
  }

  @override
  $OrganizationsCacheTable createAlias(String alias) {
    return $OrganizationsCacheTable(attachedDatabase, alias);
  }
}

class OrganizationsCacheData extends DataClass
    implements Insertable<OrganizationsCacheData> {
  final int organizationId;
  final String name;
  const OrganizationsCacheData({
    required this.organizationId,
    required this.name,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['name'] = Variable<String>(name);
    return map;
  }

  OrganizationsCacheCompanion toCompanion(bool nullToAbsent) {
    return OrganizationsCacheCompanion(
      organizationId: Value(organizationId),
      name: Value(name),
    );
  }

  factory OrganizationsCacheData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return OrganizationsCacheData(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      name: serializer.fromJson<String>(json['name']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'name': serializer.toJson<String>(name),
    };
  }

  OrganizationsCacheData copyWith({int? organizationId, String? name}) =>
      OrganizationsCacheData(
        organizationId: organizationId ?? this.organizationId,
        name: name ?? this.name,
      );
  OrganizationsCacheData copyWithCompanion(OrganizationsCacheCompanion data) {
    return OrganizationsCacheData(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      name: data.name.present ? data.name.value : this.name,
    );
  }

  @override
  String toString() {
    return (StringBuffer('OrganizationsCacheData(')
          ..write('organizationId: $organizationId, ')
          ..write('name: $name')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(organizationId, name);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is OrganizationsCacheData &&
          other.organizationId == this.organizationId &&
          other.name == this.name);
}

class OrganizationsCacheCompanion
    extends UpdateCompanion<OrganizationsCacheData> {
  final Value<int> organizationId;
  final Value<String> name;
  const OrganizationsCacheCompanion({
    this.organizationId = const Value.absent(),
    this.name = const Value.absent(),
  });
  OrganizationsCacheCompanion.insert({
    this.organizationId = const Value.absent(),
    required String name,
  }) : name = Value(name);
  static Insertable<OrganizationsCacheData> custom({
    Expression<int>? organizationId,
    Expression<String>? name,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (name != null) 'name': name,
    });
  }

  OrganizationsCacheCompanion copyWith({
    Value<int>? organizationId,
    Value<String>? name,
  }) {
    return OrganizationsCacheCompanion(
      organizationId: organizationId ?? this.organizationId,
      name: name ?? this.name,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('OrganizationsCacheCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('name: $name')
          ..write(')'))
        .toString();
  }
}

class $EntitlementsCacheTable extends EntitlementsCache
    with TableInfo<$EntitlementsCacheTable, EntitlementsCacheData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $EntitlementsCacheTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _slugMeta = const VerificationMeta('slug');
  @override
  late final GeneratedColumn<String> slug = GeneratedColumn<String>(
    'slug',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [organizationId, slug, rawJson];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'entitlements_cache';
  @override
  VerificationContext validateIntegrity(
    Insertable<EntitlementsCacheData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('slug')) {
      context.handle(
        _slugMeta,
        slug.isAcceptableOrUnknown(data['slug']!, _slugMeta),
      );
    } else if (isInserting) {
      context.missing(_slugMeta);
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, slug};
  @override
  EntitlementsCacheData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return EntitlementsCacheData(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      slug: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}slug'],
      )!,
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $EntitlementsCacheTable createAlias(String alias) {
    return $EntitlementsCacheTable(attachedDatabase, alias);
  }
}

class EntitlementsCacheData extends DataClass
    implements Insertable<EntitlementsCacheData> {
  final int organizationId;
  final String slug;
  final String rawJson;
  const EntitlementsCacheData({
    required this.organizationId,
    required this.slug,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['slug'] = Variable<String>(slug);
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  EntitlementsCacheCompanion toCompanion(bool nullToAbsent) {
    return EntitlementsCacheCompanion(
      organizationId: Value(organizationId),
      slug: Value(slug),
      rawJson: Value(rawJson),
    );
  }

  factory EntitlementsCacheData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return EntitlementsCacheData(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      slug: serializer.fromJson<String>(json['slug']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'slug': serializer.toJson<String>(slug),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  EntitlementsCacheData copyWith({
    int? organizationId,
    String? slug,
    String? rawJson,
  }) => EntitlementsCacheData(
    organizationId: organizationId ?? this.organizationId,
    slug: slug ?? this.slug,
    rawJson: rawJson ?? this.rawJson,
  );
  EntitlementsCacheData copyWithCompanion(EntitlementsCacheCompanion data) {
    return EntitlementsCacheData(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      slug: data.slug.present ? data.slug.value : this.slug,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('EntitlementsCacheData(')
          ..write('organizationId: $organizationId, ')
          ..write('slug: $slug, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(organizationId, slug, rawJson);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is EntitlementsCacheData &&
          other.organizationId == this.organizationId &&
          other.slug == this.slug &&
          other.rawJson == this.rawJson);
}

class EntitlementsCacheCompanion
    extends UpdateCompanion<EntitlementsCacheData> {
  final Value<int> organizationId;
  final Value<String> slug;
  final Value<String> rawJson;
  final Value<int> rowid;
  const EntitlementsCacheCompanion({
    this.organizationId = const Value.absent(),
    this.slug = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  EntitlementsCacheCompanion.insert({
    required int organizationId,
    required String slug,
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       slug = Value(slug),
       rawJson = Value(rawJson);
  static Insertable<EntitlementsCacheData> custom({
    Expression<int>? organizationId,
    Expression<String>? slug,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (slug != null) 'slug': slug,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  EntitlementsCacheCompanion copyWith({
    Value<int>? organizationId,
    Value<String>? slug,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return EntitlementsCacheCompanion(
      organizationId: organizationId ?? this.organizationId,
      slug: slug ?? this.slug,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (slug.present) {
      map['slug'] = Variable<String>(slug.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('EntitlementsCacheCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('slug: $slug, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CategoriesTable extends Categories
    with TableInfo<$CategoriesTable, Category> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CategoriesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    id,
    shopId,
    name,
    status,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'categories';
  @override
  VerificationContext validateIntegrity(
    Insertable<Category> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, id};
  @override
  Category map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Category(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      ),
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $CategoriesTable createAlias(String alias) {
    return $CategoriesTable(attachedDatabase, alias);
  }
}

class Category extends DataClass implements Insertable<Category> {
  final int organizationId;
  final int id;
  final int? shopId;
  final String name;
  final String? status;
  final String rawJson;
  const Category({
    required this.organizationId,
    required this.id,
    this.shopId,
    required this.name,
    this.status,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['id'] = Variable<int>(id);
    if (!nullToAbsent || shopId != null) {
      map['shop_id'] = Variable<int>(shopId);
    }
    map['name'] = Variable<String>(name);
    if (!nullToAbsent || status != null) {
      map['status'] = Variable<String>(status);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  CategoriesCompanion toCompanion(bool nullToAbsent) {
    return CategoriesCompanion(
      organizationId: Value(organizationId),
      id: Value(id),
      shopId: shopId == null && nullToAbsent
          ? const Value.absent()
          : Value(shopId),
      name: Value(name),
      status: status == null && nullToAbsent
          ? const Value.absent()
          : Value(status),
      rawJson: Value(rawJson),
    );
  }

  factory Category.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Category(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      id: serializer.fromJson<int>(json['id']),
      shopId: serializer.fromJson<int?>(json['shopId']),
      name: serializer.fromJson<String>(json['name']),
      status: serializer.fromJson<String?>(json['status']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'id': serializer.toJson<int>(id),
      'shopId': serializer.toJson<int?>(shopId),
      'name': serializer.toJson<String>(name),
      'status': serializer.toJson<String?>(status),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  Category copyWith({
    int? organizationId,
    int? id,
    Value<int?> shopId = const Value.absent(),
    String? name,
    Value<String?> status = const Value.absent(),
    String? rawJson,
  }) => Category(
    organizationId: organizationId ?? this.organizationId,
    id: id ?? this.id,
    shopId: shopId.present ? shopId.value : this.shopId,
    name: name ?? this.name,
    status: status.present ? status.value : this.status,
    rawJson: rawJson ?? this.rawJson,
  );
  Category copyWithCompanion(CategoriesCompanion data) {
    return Category(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      id: data.id.present ? data.id.value : this.id,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      name: data.name.present ? data.name.value : this.name,
      status: data.status.present ? data.status.value : this.status,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Category(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('name: $name, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(organizationId, id, shopId, name, status, rawJson);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Category &&
          other.organizationId == this.organizationId &&
          other.id == this.id &&
          other.shopId == this.shopId &&
          other.name == this.name &&
          other.status == this.status &&
          other.rawJson == this.rawJson);
}

class CategoriesCompanion extends UpdateCompanion<Category> {
  final Value<int> organizationId;
  final Value<int> id;
  final Value<int?> shopId;
  final Value<String> name;
  final Value<String?> status;
  final Value<String> rawJson;
  final Value<int> rowid;
  const CategoriesCompanion({
    this.organizationId = const Value.absent(),
    this.id = const Value.absent(),
    this.shopId = const Value.absent(),
    this.name = const Value.absent(),
    this.status = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  CategoriesCompanion.insert({
    required int organizationId,
    required int id,
    this.shopId = const Value.absent(),
    required String name,
    this.status = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       id = Value(id),
       name = Value(name),
       rawJson = Value(rawJson);
  static Insertable<Category> custom({
    Expression<int>? organizationId,
    Expression<int>? id,
    Expression<int>? shopId,
    Expression<String>? name,
    Expression<String>? status,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (id != null) 'id': id,
      if (shopId != null) 'shop_id': shopId,
      if (name != null) 'name': name,
      if (status != null) 'status': status,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  CategoriesCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? id,
    Value<int?>? shopId,
    Value<String>? name,
    Value<String?>? status,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return CategoriesCompanion(
      organizationId: organizationId ?? this.organizationId,
      id: id ?? this.id,
      shopId: shopId ?? this.shopId,
      name: name ?? this.name,
      status: status ?? this.status,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CategoriesCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('name: $name, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ProductsTable extends Products with TableInfo<$ProductsTable, Product> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ProductsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _categoryIdMeta = const VerificationMeta(
    'categoryId',
  );
  @override
  late final GeneratedColumn<int> categoryId = GeneratedColumn<int>(
    'category_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _skuMeta = const VerificationMeta('sku');
  @override
  late final GeneratedColumn<String> sku = GeneratedColumn<String>(
    'sku',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _barcodeMeta = const VerificationMeta(
    'barcode',
  );
  @override
  late final GeneratedColumn<String> barcode = GeneratedColumn<String>(
    'barcode',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _salePriceMeta = const VerificationMeta(
    'salePrice',
  );
  @override
  late final GeneratedColumn<int> salePrice = GeneratedColumn<int>(
    'sale_price',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _purchasePriceMeta = const VerificationMeta(
    'purchasePrice',
  );
  @override
  late final GeneratedColumn<int> purchasePrice = GeneratedColumn<int>(
    'purchase_price',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    id,
    shopId,
    categoryId,
    name,
    sku,
    barcode,
    salePrice,
    purchasePrice,
    status,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'products';
  @override
  VerificationContext validateIntegrity(
    Insertable<Product> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    } else if (isInserting) {
      context.missing(_shopIdMeta);
    }
    if (data.containsKey('category_id')) {
      context.handle(
        _categoryIdMeta,
        categoryId.isAcceptableOrUnknown(data['category_id']!, _categoryIdMeta),
      );
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('sku')) {
      context.handle(
        _skuMeta,
        sku.isAcceptableOrUnknown(data['sku']!, _skuMeta),
      );
    }
    if (data.containsKey('barcode')) {
      context.handle(
        _barcodeMeta,
        barcode.isAcceptableOrUnknown(data['barcode']!, _barcodeMeta),
      );
    }
    if (data.containsKey('sale_price')) {
      context.handle(
        _salePriceMeta,
        salePrice.isAcceptableOrUnknown(data['sale_price']!, _salePriceMeta),
      );
    } else if (isInserting) {
      context.missing(_salePriceMeta);
    }
    if (data.containsKey('purchase_price')) {
      context.handle(
        _purchasePriceMeta,
        purchasePrice.isAcceptableOrUnknown(
          data['purchase_price']!,
          _purchasePriceMeta,
        ),
      );
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, id};
  @override
  Product map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Product(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      )!,
      categoryId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}category_id'],
      ),
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      sku: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sku'],
      ),
      barcode: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}barcode'],
      ),
      salePrice: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}sale_price'],
      )!,
      purchasePrice: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}purchase_price'],
      ),
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $ProductsTable createAlias(String alias) {
    return $ProductsTable(attachedDatabase, alias);
  }
}

class Product extends DataClass implements Insertable<Product> {
  final int organizationId;
  final int id;
  final int shopId;
  final int? categoryId;
  final String name;
  final String? sku;
  final String? barcode;
  final int salePrice;
  final int? purchasePrice;
  final String? status;
  final String rawJson;
  const Product({
    required this.organizationId,
    required this.id,
    required this.shopId,
    this.categoryId,
    required this.name,
    this.sku,
    this.barcode,
    required this.salePrice,
    this.purchasePrice,
    this.status,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['id'] = Variable<int>(id);
    map['shop_id'] = Variable<int>(shopId);
    if (!nullToAbsent || categoryId != null) {
      map['category_id'] = Variable<int>(categoryId);
    }
    map['name'] = Variable<String>(name);
    if (!nullToAbsent || sku != null) {
      map['sku'] = Variable<String>(sku);
    }
    if (!nullToAbsent || barcode != null) {
      map['barcode'] = Variable<String>(barcode);
    }
    map['sale_price'] = Variable<int>(salePrice);
    if (!nullToAbsent || purchasePrice != null) {
      map['purchase_price'] = Variable<int>(purchasePrice);
    }
    if (!nullToAbsent || status != null) {
      map['status'] = Variable<String>(status);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  ProductsCompanion toCompanion(bool nullToAbsent) {
    return ProductsCompanion(
      organizationId: Value(organizationId),
      id: Value(id),
      shopId: Value(shopId),
      categoryId: categoryId == null && nullToAbsent
          ? const Value.absent()
          : Value(categoryId),
      name: Value(name),
      sku: sku == null && nullToAbsent ? const Value.absent() : Value(sku),
      barcode: barcode == null && nullToAbsent
          ? const Value.absent()
          : Value(barcode),
      salePrice: Value(salePrice),
      purchasePrice: purchasePrice == null && nullToAbsent
          ? const Value.absent()
          : Value(purchasePrice),
      status: status == null && nullToAbsent
          ? const Value.absent()
          : Value(status),
      rawJson: Value(rawJson),
    );
  }

  factory Product.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Product(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      id: serializer.fromJson<int>(json['id']),
      shopId: serializer.fromJson<int>(json['shopId']),
      categoryId: serializer.fromJson<int?>(json['categoryId']),
      name: serializer.fromJson<String>(json['name']),
      sku: serializer.fromJson<String?>(json['sku']),
      barcode: serializer.fromJson<String?>(json['barcode']),
      salePrice: serializer.fromJson<int>(json['salePrice']),
      purchasePrice: serializer.fromJson<int?>(json['purchasePrice']),
      status: serializer.fromJson<String?>(json['status']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'id': serializer.toJson<int>(id),
      'shopId': serializer.toJson<int>(shopId),
      'categoryId': serializer.toJson<int?>(categoryId),
      'name': serializer.toJson<String>(name),
      'sku': serializer.toJson<String?>(sku),
      'barcode': serializer.toJson<String?>(barcode),
      'salePrice': serializer.toJson<int>(salePrice),
      'purchasePrice': serializer.toJson<int?>(purchasePrice),
      'status': serializer.toJson<String?>(status),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  Product copyWith({
    int? organizationId,
    int? id,
    int? shopId,
    Value<int?> categoryId = const Value.absent(),
    String? name,
    Value<String?> sku = const Value.absent(),
    Value<String?> barcode = const Value.absent(),
    int? salePrice,
    Value<int?> purchasePrice = const Value.absent(),
    Value<String?> status = const Value.absent(),
    String? rawJson,
  }) => Product(
    organizationId: organizationId ?? this.organizationId,
    id: id ?? this.id,
    shopId: shopId ?? this.shopId,
    categoryId: categoryId.present ? categoryId.value : this.categoryId,
    name: name ?? this.name,
    sku: sku.present ? sku.value : this.sku,
    barcode: barcode.present ? barcode.value : this.barcode,
    salePrice: salePrice ?? this.salePrice,
    purchasePrice: purchasePrice.present
        ? purchasePrice.value
        : this.purchasePrice,
    status: status.present ? status.value : this.status,
    rawJson: rawJson ?? this.rawJson,
  );
  Product copyWithCompanion(ProductsCompanion data) {
    return Product(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      id: data.id.present ? data.id.value : this.id,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      categoryId: data.categoryId.present
          ? data.categoryId.value
          : this.categoryId,
      name: data.name.present ? data.name.value : this.name,
      sku: data.sku.present ? data.sku.value : this.sku,
      barcode: data.barcode.present ? data.barcode.value : this.barcode,
      salePrice: data.salePrice.present ? data.salePrice.value : this.salePrice,
      purchasePrice: data.purchasePrice.present
          ? data.purchasePrice.value
          : this.purchasePrice,
      status: data.status.present ? data.status.value : this.status,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Product(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('categoryId: $categoryId, ')
          ..write('name: $name, ')
          ..write('sku: $sku, ')
          ..write('barcode: $barcode, ')
          ..write('salePrice: $salePrice, ')
          ..write('purchasePrice: $purchasePrice, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    organizationId,
    id,
    shopId,
    categoryId,
    name,
    sku,
    barcode,
    salePrice,
    purchasePrice,
    status,
    rawJson,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Product &&
          other.organizationId == this.organizationId &&
          other.id == this.id &&
          other.shopId == this.shopId &&
          other.categoryId == this.categoryId &&
          other.name == this.name &&
          other.sku == this.sku &&
          other.barcode == this.barcode &&
          other.salePrice == this.salePrice &&
          other.purchasePrice == this.purchasePrice &&
          other.status == this.status &&
          other.rawJson == this.rawJson);
}

class ProductsCompanion extends UpdateCompanion<Product> {
  final Value<int> organizationId;
  final Value<int> id;
  final Value<int> shopId;
  final Value<int?> categoryId;
  final Value<String> name;
  final Value<String?> sku;
  final Value<String?> barcode;
  final Value<int> salePrice;
  final Value<int?> purchasePrice;
  final Value<String?> status;
  final Value<String> rawJson;
  final Value<int> rowid;
  const ProductsCompanion({
    this.organizationId = const Value.absent(),
    this.id = const Value.absent(),
    this.shopId = const Value.absent(),
    this.categoryId = const Value.absent(),
    this.name = const Value.absent(),
    this.sku = const Value.absent(),
    this.barcode = const Value.absent(),
    this.salePrice = const Value.absent(),
    this.purchasePrice = const Value.absent(),
    this.status = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ProductsCompanion.insert({
    required int organizationId,
    required int id,
    required int shopId,
    this.categoryId = const Value.absent(),
    required String name,
    this.sku = const Value.absent(),
    this.barcode = const Value.absent(),
    required int salePrice,
    this.purchasePrice = const Value.absent(),
    this.status = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       id = Value(id),
       shopId = Value(shopId),
       name = Value(name),
       salePrice = Value(salePrice),
       rawJson = Value(rawJson);
  static Insertable<Product> custom({
    Expression<int>? organizationId,
    Expression<int>? id,
    Expression<int>? shopId,
    Expression<int>? categoryId,
    Expression<String>? name,
    Expression<String>? sku,
    Expression<String>? barcode,
    Expression<int>? salePrice,
    Expression<int>? purchasePrice,
    Expression<String>? status,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (id != null) 'id': id,
      if (shopId != null) 'shop_id': shopId,
      if (categoryId != null) 'category_id': categoryId,
      if (name != null) 'name': name,
      if (sku != null) 'sku': sku,
      if (barcode != null) 'barcode': barcode,
      if (salePrice != null) 'sale_price': salePrice,
      if (purchasePrice != null) 'purchase_price': purchasePrice,
      if (status != null) 'status': status,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ProductsCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? id,
    Value<int>? shopId,
    Value<int?>? categoryId,
    Value<String>? name,
    Value<String?>? sku,
    Value<String?>? barcode,
    Value<int>? salePrice,
    Value<int?>? purchasePrice,
    Value<String?>? status,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return ProductsCompanion(
      organizationId: organizationId ?? this.organizationId,
      id: id ?? this.id,
      shopId: shopId ?? this.shopId,
      categoryId: categoryId ?? this.categoryId,
      name: name ?? this.name,
      sku: sku ?? this.sku,
      barcode: barcode ?? this.barcode,
      salePrice: salePrice ?? this.salePrice,
      purchasePrice: purchasePrice ?? this.purchasePrice,
      status: status ?? this.status,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (categoryId.present) {
      map['category_id'] = Variable<int>(categoryId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (sku.present) {
      map['sku'] = Variable<String>(sku.value);
    }
    if (barcode.present) {
      map['barcode'] = Variable<String>(barcode.value);
    }
    if (salePrice.present) {
      map['sale_price'] = Variable<int>(salePrice.value);
    }
    if (purchasePrice.present) {
      map['purchase_price'] = Variable<int>(purchasePrice.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ProductsCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('categoryId: $categoryId, ')
          ..write('name: $name, ')
          ..write('sku: $sku, ')
          ..write('barcode: $barcode, ')
          ..write('salePrice: $salePrice, ')
          ..write('purchasePrice: $purchasePrice, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ProductVariantsTable extends ProductVariants
    with TableInfo<$ProductVariantsTable, ProductVariant> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ProductVariantsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _productIdMeta = const VerificationMeta(
    'productId',
  );
  @override
  late final GeneratedColumn<int> productId = GeneratedColumn<int>(
    'product_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _skuMeta = const VerificationMeta('sku');
  @override
  late final GeneratedColumn<String> sku = GeneratedColumn<String>(
    'sku',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _barcodeMeta = const VerificationMeta(
    'barcode',
  );
  @override
  late final GeneratedColumn<String> barcode = GeneratedColumn<String>(
    'barcode',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _salePriceMeta = const VerificationMeta(
    'salePrice',
  );
  @override
  late final GeneratedColumn<int> salePrice = GeneratedColumn<int>(
    'sale_price',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _purchasePriceMeta = const VerificationMeta(
    'purchasePrice',
  );
  @override
  late final GeneratedColumn<int> purchasePrice = GeneratedColumn<int>(
    'purchase_price',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _attributesJsonMeta = const VerificationMeta(
    'attributesJson',
  );
  @override
  late final GeneratedColumn<String> attributesJson = GeneratedColumn<String>(
    'attributes_json',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    id,
    shopId,
    productId,
    name,
    sku,
    barcode,
    salePrice,
    purchasePrice,
    attributesJson,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'product_variants';
  @override
  VerificationContext validateIntegrity(
    Insertable<ProductVariant> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    } else if (isInserting) {
      context.missing(_shopIdMeta);
    }
    if (data.containsKey('product_id')) {
      context.handle(
        _productIdMeta,
        productId.isAcceptableOrUnknown(data['product_id']!, _productIdMeta),
      );
    } else if (isInserting) {
      context.missing(_productIdMeta);
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('sku')) {
      context.handle(
        _skuMeta,
        sku.isAcceptableOrUnknown(data['sku']!, _skuMeta),
      );
    }
    if (data.containsKey('barcode')) {
      context.handle(
        _barcodeMeta,
        barcode.isAcceptableOrUnknown(data['barcode']!, _barcodeMeta),
      );
    }
    if (data.containsKey('sale_price')) {
      context.handle(
        _salePriceMeta,
        salePrice.isAcceptableOrUnknown(data['sale_price']!, _salePriceMeta),
      );
    } else if (isInserting) {
      context.missing(_salePriceMeta);
    }
    if (data.containsKey('purchase_price')) {
      context.handle(
        _purchasePriceMeta,
        purchasePrice.isAcceptableOrUnknown(
          data['purchase_price']!,
          _purchasePriceMeta,
        ),
      );
    }
    if (data.containsKey('attributes_json')) {
      context.handle(
        _attributesJsonMeta,
        attributesJson.isAcceptableOrUnknown(
          data['attributes_json']!,
          _attributesJsonMeta,
        ),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, id};
  @override
  ProductVariant map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return ProductVariant(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      )!,
      productId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}product_id'],
      )!,
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      sku: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sku'],
      ),
      barcode: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}barcode'],
      ),
      salePrice: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}sale_price'],
      )!,
      purchasePrice: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}purchase_price'],
      ),
      attributesJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}attributes_json'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $ProductVariantsTable createAlias(String alias) {
    return $ProductVariantsTable(attachedDatabase, alias);
  }
}

class ProductVariant extends DataClass implements Insertable<ProductVariant> {
  final int organizationId;
  final int id;
  final int shopId;
  final int productId;
  final String name;
  final String? sku;
  final String? barcode;
  final int salePrice;
  final int? purchasePrice;
  final String? attributesJson;
  final String rawJson;
  const ProductVariant({
    required this.organizationId,
    required this.id,
    required this.shopId,
    required this.productId,
    required this.name,
    this.sku,
    this.barcode,
    required this.salePrice,
    this.purchasePrice,
    this.attributesJson,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['id'] = Variable<int>(id);
    map['shop_id'] = Variable<int>(shopId);
    map['product_id'] = Variable<int>(productId);
    map['name'] = Variable<String>(name);
    if (!nullToAbsent || sku != null) {
      map['sku'] = Variable<String>(sku);
    }
    if (!nullToAbsent || barcode != null) {
      map['barcode'] = Variable<String>(barcode);
    }
    map['sale_price'] = Variable<int>(salePrice);
    if (!nullToAbsent || purchasePrice != null) {
      map['purchase_price'] = Variable<int>(purchasePrice);
    }
    if (!nullToAbsent || attributesJson != null) {
      map['attributes_json'] = Variable<String>(attributesJson);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  ProductVariantsCompanion toCompanion(bool nullToAbsent) {
    return ProductVariantsCompanion(
      organizationId: Value(organizationId),
      id: Value(id),
      shopId: Value(shopId),
      productId: Value(productId),
      name: Value(name),
      sku: sku == null && nullToAbsent ? const Value.absent() : Value(sku),
      barcode: barcode == null && nullToAbsent
          ? const Value.absent()
          : Value(barcode),
      salePrice: Value(salePrice),
      purchasePrice: purchasePrice == null && nullToAbsent
          ? const Value.absent()
          : Value(purchasePrice),
      attributesJson: attributesJson == null && nullToAbsent
          ? const Value.absent()
          : Value(attributesJson),
      rawJson: Value(rawJson),
    );
  }

  factory ProductVariant.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return ProductVariant(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      id: serializer.fromJson<int>(json['id']),
      shopId: serializer.fromJson<int>(json['shopId']),
      productId: serializer.fromJson<int>(json['productId']),
      name: serializer.fromJson<String>(json['name']),
      sku: serializer.fromJson<String?>(json['sku']),
      barcode: serializer.fromJson<String?>(json['barcode']),
      salePrice: serializer.fromJson<int>(json['salePrice']),
      purchasePrice: serializer.fromJson<int?>(json['purchasePrice']),
      attributesJson: serializer.fromJson<String?>(json['attributesJson']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'id': serializer.toJson<int>(id),
      'shopId': serializer.toJson<int>(shopId),
      'productId': serializer.toJson<int>(productId),
      'name': serializer.toJson<String>(name),
      'sku': serializer.toJson<String?>(sku),
      'barcode': serializer.toJson<String?>(barcode),
      'salePrice': serializer.toJson<int>(salePrice),
      'purchasePrice': serializer.toJson<int?>(purchasePrice),
      'attributesJson': serializer.toJson<String?>(attributesJson),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  ProductVariant copyWith({
    int? organizationId,
    int? id,
    int? shopId,
    int? productId,
    String? name,
    Value<String?> sku = const Value.absent(),
    Value<String?> barcode = const Value.absent(),
    int? salePrice,
    Value<int?> purchasePrice = const Value.absent(),
    Value<String?> attributesJson = const Value.absent(),
    String? rawJson,
  }) => ProductVariant(
    organizationId: organizationId ?? this.organizationId,
    id: id ?? this.id,
    shopId: shopId ?? this.shopId,
    productId: productId ?? this.productId,
    name: name ?? this.name,
    sku: sku.present ? sku.value : this.sku,
    barcode: barcode.present ? barcode.value : this.barcode,
    salePrice: salePrice ?? this.salePrice,
    purchasePrice: purchasePrice.present
        ? purchasePrice.value
        : this.purchasePrice,
    attributesJson: attributesJson.present
        ? attributesJson.value
        : this.attributesJson,
    rawJson: rawJson ?? this.rawJson,
  );
  ProductVariant copyWithCompanion(ProductVariantsCompanion data) {
    return ProductVariant(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      id: data.id.present ? data.id.value : this.id,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      productId: data.productId.present ? data.productId.value : this.productId,
      name: data.name.present ? data.name.value : this.name,
      sku: data.sku.present ? data.sku.value : this.sku,
      barcode: data.barcode.present ? data.barcode.value : this.barcode,
      salePrice: data.salePrice.present ? data.salePrice.value : this.salePrice,
      purchasePrice: data.purchasePrice.present
          ? data.purchasePrice.value
          : this.purchasePrice,
      attributesJson: data.attributesJson.present
          ? data.attributesJson.value
          : this.attributesJson,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('ProductVariant(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('productId: $productId, ')
          ..write('name: $name, ')
          ..write('sku: $sku, ')
          ..write('barcode: $barcode, ')
          ..write('salePrice: $salePrice, ')
          ..write('purchasePrice: $purchasePrice, ')
          ..write('attributesJson: $attributesJson, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    organizationId,
    id,
    shopId,
    productId,
    name,
    sku,
    barcode,
    salePrice,
    purchasePrice,
    attributesJson,
    rawJson,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is ProductVariant &&
          other.organizationId == this.organizationId &&
          other.id == this.id &&
          other.shopId == this.shopId &&
          other.productId == this.productId &&
          other.name == this.name &&
          other.sku == this.sku &&
          other.barcode == this.barcode &&
          other.salePrice == this.salePrice &&
          other.purchasePrice == this.purchasePrice &&
          other.attributesJson == this.attributesJson &&
          other.rawJson == this.rawJson);
}

class ProductVariantsCompanion extends UpdateCompanion<ProductVariant> {
  final Value<int> organizationId;
  final Value<int> id;
  final Value<int> shopId;
  final Value<int> productId;
  final Value<String> name;
  final Value<String?> sku;
  final Value<String?> barcode;
  final Value<int> salePrice;
  final Value<int?> purchasePrice;
  final Value<String?> attributesJson;
  final Value<String> rawJson;
  final Value<int> rowid;
  const ProductVariantsCompanion({
    this.organizationId = const Value.absent(),
    this.id = const Value.absent(),
    this.shopId = const Value.absent(),
    this.productId = const Value.absent(),
    this.name = const Value.absent(),
    this.sku = const Value.absent(),
    this.barcode = const Value.absent(),
    this.salePrice = const Value.absent(),
    this.purchasePrice = const Value.absent(),
    this.attributesJson = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ProductVariantsCompanion.insert({
    required int organizationId,
    required int id,
    required int shopId,
    required int productId,
    required String name,
    this.sku = const Value.absent(),
    this.barcode = const Value.absent(),
    required int salePrice,
    this.purchasePrice = const Value.absent(),
    this.attributesJson = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       id = Value(id),
       shopId = Value(shopId),
       productId = Value(productId),
       name = Value(name),
       salePrice = Value(salePrice),
       rawJson = Value(rawJson);
  static Insertable<ProductVariant> custom({
    Expression<int>? organizationId,
    Expression<int>? id,
    Expression<int>? shopId,
    Expression<int>? productId,
    Expression<String>? name,
    Expression<String>? sku,
    Expression<String>? barcode,
    Expression<int>? salePrice,
    Expression<int>? purchasePrice,
    Expression<String>? attributesJson,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (id != null) 'id': id,
      if (shopId != null) 'shop_id': shopId,
      if (productId != null) 'product_id': productId,
      if (name != null) 'name': name,
      if (sku != null) 'sku': sku,
      if (barcode != null) 'barcode': barcode,
      if (salePrice != null) 'sale_price': salePrice,
      if (purchasePrice != null) 'purchase_price': purchasePrice,
      if (attributesJson != null) 'attributes_json': attributesJson,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ProductVariantsCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? id,
    Value<int>? shopId,
    Value<int>? productId,
    Value<String>? name,
    Value<String?>? sku,
    Value<String?>? barcode,
    Value<int>? salePrice,
    Value<int?>? purchasePrice,
    Value<String?>? attributesJson,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return ProductVariantsCompanion(
      organizationId: organizationId ?? this.organizationId,
      id: id ?? this.id,
      shopId: shopId ?? this.shopId,
      productId: productId ?? this.productId,
      name: name ?? this.name,
      sku: sku ?? this.sku,
      barcode: barcode ?? this.barcode,
      salePrice: salePrice ?? this.salePrice,
      purchasePrice: purchasePrice ?? this.purchasePrice,
      attributesJson: attributesJson ?? this.attributesJson,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (productId.present) {
      map['product_id'] = Variable<int>(productId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (sku.present) {
      map['sku'] = Variable<String>(sku.value);
    }
    if (barcode.present) {
      map['barcode'] = Variable<String>(barcode.value);
    }
    if (salePrice.present) {
      map['sale_price'] = Variable<int>(salePrice.value);
    }
    if (purchasePrice.present) {
      map['purchase_price'] = Variable<int>(purchasePrice.value);
    }
    if (attributesJson.present) {
      map['attributes_json'] = Variable<String>(attributesJson.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ProductVariantsCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('productId: $productId, ')
          ..write('name: $name, ')
          ..write('sku: $sku, ')
          ..write('barcode: $barcode, ')
          ..write('salePrice: $salePrice, ')
          ..write('purchasePrice: $purchasePrice, ')
          ..write('attributesJson: $attributesJson, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CustomersTable extends Customers
    with TableInfo<$CustomersTable, Customer> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CustomersTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _phoneMeta = const VerificationMeta('phone');
  @override
  late final GeneratedColumn<String> phone = GeneratedColumn<String>(
    'phone',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _emailMeta = const VerificationMeta('email');
  @override
  late final GeneratedColumn<String> email = GeneratedColumn<String>(
    'email',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    id,
    shopId,
    name,
    phone,
    email,
    status,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'customers';
  @override
  VerificationContext validateIntegrity(
    Insertable<Customer> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('phone')) {
      context.handle(
        _phoneMeta,
        phone.isAcceptableOrUnknown(data['phone']!, _phoneMeta),
      );
    }
    if (data.containsKey('email')) {
      context.handle(
        _emailMeta,
        email.isAcceptableOrUnknown(data['email']!, _emailMeta),
      );
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, id};
  @override
  Customer map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Customer(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      ),
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      phone: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}phone'],
      ),
      email: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}email'],
      ),
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $CustomersTable createAlias(String alias) {
    return $CustomersTable(attachedDatabase, alias);
  }
}

class Customer extends DataClass implements Insertable<Customer> {
  final int organizationId;
  final int id;
  final int? shopId;
  final String name;
  final String? phone;
  final String? email;
  final String? status;
  final String rawJson;
  const Customer({
    required this.organizationId,
    required this.id,
    this.shopId,
    required this.name,
    this.phone,
    this.email,
    this.status,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['id'] = Variable<int>(id);
    if (!nullToAbsent || shopId != null) {
      map['shop_id'] = Variable<int>(shopId);
    }
    map['name'] = Variable<String>(name);
    if (!nullToAbsent || phone != null) {
      map['phone'] = Variable<String>(phone);
    }
    if (!nullToAbsent || email != null) {
      map['email'] = Variable<String>(email);
    }
    if (!nullToAbsent || status != null) {
      map['status'] = Variable<String>(status);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  CustomersCompanion toCompanion(bool nullToAbsent) {
    return CustomersCompanion(
      organizationId: Value(organizationId),
      id: Value(id),
      shopId: shopId == null && nullToAbsent
          ? const Value.absent()
          : Value(shopId),
      name: Value(name),
      phone: phone == null && nullToAbsent
          ? const Value.absent()
          : Value(phone),
      email: email == null && nullToAbsent
          ? const Value.absent()
          : Value(email),
      status: status == null && nullToAbsent
          ? const Value.absent()
          : Value(status),
      rawJson: Value(rawJson),
    );
  }

  factory Customer.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Customer(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      id: serializer.fromJson<int>(json['id']),
      shopId: serializer.fromJson<int?>(json['shopId']),
      name: serializer.fromJson<String>(json['name']),
      phone: serializer.fromJson<String?>(json['phone']),
      email: serializer.fromJson<String?>(json['email']),
      status: serializer.fromJson<String?>(json['status']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'id': serializer.toJson<int>(id),
      'shopId': serializer.toJson<int?>(shopId),
      'name': serializer.toJson<String>(name),
      'phone': serializer.toJson<String?>(phone),
      'email': serializer.toJson<String?>(email),
      'status': serializer.toJson<String?>(status),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  Customer copyWith({
    int? organizationId,
    int? id,
    Value<int?> shopId = const Value.absent(),
    String? name,
    Value<String?> phone = const Value.absent(),
    Value<String?> email = const Value.absent(),
    Value<String?> status = const Value.absent(),
    String? rawJson,
  }) => Customer(
    organizationId: organizationId ?? this.organizationId,
    id: id ?? this.id,
    shopId: shopId.present ? shopId.value : this.shopId,
    name: name ?? this.name,
    phone: phone.present ? phone.value : this.phone,
    email: email.present ? email.value : this.email,
    status: status.present ? status.value : this.status,
    rawJson: rawJson ?? this.rawJson,
  );
  Customer copyWithCompanion(CustomersCompanion data) {
    return Customer(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      id: data.id.present ? data.id.value : this.id,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      name: data.name.present ? data.name.value : this.name,
      phone: data.phone.present ? data.phone.value : this.phone,
      email: data.email.present ? data.email.value : this.email,
      status: data.status.present ? data.status.value : this.status,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Customer(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('name: $name, ')
          ..write('phone: $phone, ')
          ..write('email: $email, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    organizationId,
    id,
    shopId,
    name,
    phone,
    email,
    status,
    rawJson,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Customer &&
          other.organizationId == this.organizationId &&
          other.id == this.id &&
          other.shopId == this.shopId &&
          other.name == this.name &&
          other.phone == this.phone &&
          other.email == this.email &&
          other.status == this.status &&
          other.rawJson == this.rawJson);
}

class CustomersCompanion extends UpdateCompanion<Customer> {
  final Value<int> organizationId;
  final Value<int> id;
  final Value<int?> shopId;
  final Value<String> name;
  final Value<String?> phone;
  final Value<String?> email;
  final Value<String?> status;
  final Value<String> rawJson;
  final Value<int> rowid;
  const CustomersCompanion({
    this.organizationId = const Value.absent(),
    this.id = const Value.absent(),
    this.shopId = const Value.absent(),
    this.name = const Value.absent(),
    this.phone = const Value.absent(),
    this.email = const Value.absent(),
    this.status = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  CustomersCompanion.insert({
    required int organizationId,
    required int id,
    this.shopId = const Value.absent(),
    required String name,
    this.phone = const Value.absent(),
    this.email = const Value.absent(),
    this.status = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       id = Value(id),
       name = Value(name),
       rawJson = Value(rawJson);
  static Insertable<Customer> custom({
    Expression<int>? organizationId,
    Expression<int>? id,
    Expression<int>? shopId,
    Expression<String>? name,
    Expression<String>? phone,
    Expression<String>? email,
    Expression<String>? status,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (id != null) 'id': id,
      if (shopId != null) 'shop_id': shopId,
      if (name != null) 'name': name,
      if (phone != null) 'phone': phone,
      if (email != null) 'email': email,
      if (status != null) 'status': status,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  CustomersCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? id,
    Value<int?>? shopId,
    Value<String>? name,
    Value<String?>? phone,
    Value<String?>? email,
    Value<String?>? status,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return CustomersCompanion(
      organizationId: organizationId ?? this.organizationId,
      id: id ?? this.id,
      shopId: shopId ?? this.shopId,
      name: name ?? this.name,
      phone: phone ?? this.phone,
      email: email ?? this.email,
      status: status ?? this.status,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (phone.present) {
      map['phone'] = Variable<String>(phone.value);
    }
    if (email.present) {
      map['email'] = Variable<String>(email.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CustomersCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('name: $name, ')
          ..write('phone: $phone, ')
          ..write('email: $email, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $StockLevelsTable extends StockLevels
    with TableInfo<$StockLevelsTable, StockLevel> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $StockLevelsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _stockLocationIdMeta = const VerificationMeta(
    'stockLocationId',
  );
  @override
  late final GeneratedColumn<int> stockLocationId = GeneratedColumn<int>(
    'stock_location_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _stockIdentityMeta = const VerificationMeta(
    'stockIdentity',
  );
  @override
  late final GeneratedColumn<String> stockIdentity = GeneratedColumn<String>(
    'stock_identity',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _productIdMeta = const VerificationMeta(
    'productId',
  );
  @override
  late final GeneratedColumn<int> productId = GeneratedColumn<int>(
    'product_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _variantIdMeta = const VerificationMeta(
    'variantId',
  );
  @override
  late final GeneratedColumn<int> variantId = GeneratedColumn<int>(
    'variant_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _quantityMeta = const VerificationMeta(
    'quantity',
  );
  @override
  late final GeneratedColumn<double> quantity = GeneratedColumn<double>(
    'quantity',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _reservedQuantityMeta = const VerificationMeta(
    'reservedQuantity',
  );
  @override
  late final GeneratedColumn<double> reservedQuantity = GeneratedColumn<double>(
    'reserved_quantity',
    aliasedName,
    true,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    shopId,
    stockLocationId,
    stockIdentity,
    productId,
    variantId,
    quantity,
    reservedQuantity,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'stock_levels';
  @override
  VerificationContext validateIntegrity(
    Insertable<StockLevel> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    } else if (isInserting) {
      context.missing(_shopIdMeta);
    }
    if (data.containsKey('stock_location_id')) {
      context.handle(
        _stockLocationIdMeta,
        stockLocationId.isAcceptableOrUnknown(
          data['stock_location_id']!,
          _stockLocationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_stockLocationIdMeta);
    }
    if (data.containsKey('stock_identity')) {
      context.handle(
        _stockIdentityMeta,
        stockIdentity.isAcceptableOrUnknown(
          data['stock_identity']!,
          _stockIdentityMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_stockIdentityMeta);
    }
    if (data.containsKey('product_id')) {
      context.handle(
        _productIdMeta,
        productId.isAcceptableOrUnknown(data['product_id']!, _productIdMeta),
      );
    }
    if (data.containsKey('variant_id')) {
      context.handle(
        _variantIdMeta,
        variantId.isAcceptableOrUnknown(data['variant_id']!, _variantIdMeta),
      );
    }
    if (data.containsKey('quantity')) {
      context.handle(
        _quantityMeta,
        quantity.isAcceptableOrUnknown(data['quantity']!, _quantityMeta),
      );
    } else if (isInserting) {
      context.missing(_quantityMeta);
    }
    if (data.containsKey('reserved_quantity')) {
      context.handle(
        _reservedQuantityMeta,
        reservedQuantity.isAcceptableOrUnknown(
          data['reserved_quantity']!,
          _reservedQuantityMeta,
        ),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {
    organizationId,
    stockLocationId,
    stockIdentity,
  };
  @override
  StockLevel map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return StockLevel(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      )!,
      stockLocationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}stock_location_id'],
      )!,
      stockIdentity: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}stock_identity'],
      )!,
      productId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}product_id'],
      ),
      variantId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}variant_id'],
      ),
      quantity: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}quantity'],
      )!,
      reservedQuantity: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}reserved_quantity'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $StockLevelsTable createAlias(String alias) {
    return $StockLevelsTable(attachedDatabase, alias);
  }
}

class StockLevel extends DataClass implements Insertable<StockLevel> {
  final int organizationId;
  final int shopId;
  final int stockLocationId;
  final String stockIdentity;
  final int? productId;
  final int? variantId;
  final double quantity;
  final double? reservedQuantity;
  final String rawJson;
  const StockLevel({
    required this.organizationId,
    required this.shopId,
    required this.stockLocationId,
    required this.stockIdentity,
    this.productId,
    this.variantId,
    required this.quantity,
    this.reservedQuantity,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['shop_id'] = Variable<int>(shopId);
    map['stock_location_id'] = Variable<int>(stockLocationId);
    map['stock_identity'] = Variable<String>(stockIdentity);
    if (!nullToAbsent || productId != null) {
      map['product_id'] = Variable<int>(productId);
    }
    if (!nullToAbsent || variantId != null) {
      map['variant_id'] = Variable<int>(variantId);
    }
    map['quantity'] = Variable<double>(quantity);
    if (!nullToAbsent || reservedQuantity != null) {
      map['reserved_quantity'] = Variable<double>(reservedQuantity);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  StockLevelsCompanion toCompanion(bool nullToAbsent) {
    return StockLevelsCompanion(
      organizationId: Value(organizationId),
      shopId: Value(shopId),
      stockLocationId: Value(stockLocationId),
      stockIdentity: Value(stockIdentity),
      productId: productId == null && nullToAbsent
          ? const Value.absent()
          : Value(productId),
      variantId: variantId == null && nullToAbsent
          ? const Value.absent()
          : Value(variantId),
      quantity: Value(quantity),
      reservedQuantity: reservedQuantity == null && nullToAbsent
          ? const Value.absent()
          : Value(reservedQuantity),
      rawJson: Value(rawJson),
    );
  }

  factory StockLevel.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return StockLevel(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      shopId: serializer.fromJson<int>(json['shopId']),
      stockLocationId: serializer.fromJson<int>(json['stockLocationId']),
      stockIdentity: serializer.fromJson<String>(json['stockIdentity']),
      productId: serializer.fromJson<int?>(json['productId']),
      variantId: serializer.fromJson<int?>(json['variantId']),
      quantity: serializer.fromJson<double>(json['quantity']),
      reservedQuantity: serializer.fromJson<double?>(json['reservedQuantity']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'shopId': serializer.toJson<int>(shopId),
      'stockLocationId': serializer.toJson<int>(stockLocationId),
      'stockIdentity': serializer.toJson<String>(stockIdentity),
      'productId': serializer.toJson<int?>(productId),
      'variantId': serializer.toJson<int?>(variantId),
      'quantity': serializer.toJson<double>(quantity),
      'reservedQuantity': serializer.toJson<double?>(reservedQuantity),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  StockLevel copyWith({
    int? organizationId,
    int? shopId,
    int? stockLocationId,
    String? stockIdentity,
    Value<int?> productId = const Value.absent(),
    Value<int?> variantId = const Value.absent(),
    double? quantity,
    Value<double?> reservedQuantity = const Value.absent(),
    String? rawJson,
  }) => StockLevel(
    organizationId: organizationId ?? this.organizationId,
    shopId: shopId ?? this.shopId,
    stockLocationId: stockLocationId ?? this.stockLocationId,
    stockIdentity: stockIdentity ?? this.stockIdentity,
    productId: productId.present ? productId.value : this.productId,
    variantId: variantId.present ? variantId.value : this.variantId,
    quantity: quantity ?? this.quantity,
    reservedQuantity: reservedQuantity.present
        ? reservedQuantity.value
        : this.reservedQuantity,
    rawJson: rawJson ?? this.rawJson,
  );
  StockLevel copyWithCompanion(StockLevelsCompanion data) {
    return StockLevel(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      stockLocationId: data.stockLocationId.present
          ? data.stockLocationId.value
          : this.stockLocationId,
      stockIdentity: data.stockIdentity.present
          ? data.stockIdentity.value
          : this.stockIdentity,
      productId: data.productId.present ? data.productId.value : this.productId,
      variantId: data.variantId.present ? data.variantId.value : this.variantId,
      quantity: data.quantity.present ? data.quantity.value : this.quantity,
      reservedQuantity: data.reservedQuantity.present
          ? data.reservedQuantity.value
          : this.reservedQuantity,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('StockLevel(')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('stockLocationId: $stockLocationId, ')
          ..write('stockIdentity: $stockIdentity, ')
          ..write('productId: $productId, ')
          ..write('variantId: $variantId, ')
          ..write('quantity: $quantity, ')
          ..write('reservedQuantity: $reservedQuantity, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    organizationId,
    shopId,
    stockLocationId,
    stockIdentity,
    productId,
    variantId,
    quantity,
    reservedQuantity,
    rawJson,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is StockLevel &&
          other.organizationId == this.organizationId &&
          other.shopId == this.shopId &&
          other.stockLocationId == this.stockLocationId &&
          other.stockIdentity == this.stockIdentity &&
          other.productId == this.productId &&
          other.variantId == this.variantId &&
          other.quantity == this.quantity &&
          other.reservedQuantity == this.reservedQuantity &&
          other.rawJson == this.rawJson);
}

class StockLevelsCompanion extends UpdateCompanion<StockLevel> {
  final Value<int> organizationId;
  final Value<int> shopId;
  final Value<int> stockLocationId;
  final Value<String> stockIdentity;
  final Value<int?> productId;
  final Value<int?> variantId;
  final Value<double> quantity;
  final Value<double?> reservedQuantity;
  final Value<String> rawJson;
  final Value<int> rowid;
  const StockLevelsCompanion({
    this.organizationId = const Value.absent(),
    this.shopId = const Value.absent(),
    this.stockLocationId = const Value.absent(),
    this.stockIdentity = const Value.absent(),
    this.productId = const Value.absent(),
    this.variantId = const Value.absent(),
    this.quantity = const Value.absent(),
    this.reservedQuantity = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  StockLevelsCompanion.insert({
    required int organizationId,
    required int shopId,
    required int stockLocationId,
    required String stockIdentity,
    this.productId = const Value.absent(),
    this.variantId = const Value.absent(),
    required double quantity,
    this.reservedQuantity = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       shopId = Value(shopId),
       stockLocationId = Value(stockLocationId),
       stockIdentity = Value(stockIdentity),
       quantity = Value(quantity),
       rawJson = Value(rawJson);
  static Insertable<StockLevel> custom({
    Expression<int>? organizationId,
    Expression<int>? shopId,
    Expression<int>? stockLocationId,
    Expression<String>? stockIdentity,
    Expression<int>? productId,
    Expression<int>? variantId,
    Expression<double>? quantity,
    Expression<double>? reservedQuantity,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (shopId != null) 'shop_id': shopId,
      if (stockLocationId != null) 'stock_location_id': stockLocationId,
      if (stockIdentity != null) 'stock_identity': stockIdentity,
      if (productId != null) 'product_id': productId,
      if (variantId != null) 'variant_id': variantId,
      if (quantity != null) 'quantity': quantity,
      if (reservedQuantity != null) 'reserved_quantity': reservedQuantity,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  StockLevelsCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? shopId,
    Value<int>? stockLocationId,
    Value<String>? stockIdentity,
    Value<int?>? productId,
    Value<int?>? variantId,
    Value<double>? quantity,
    Value<double?>? reservedQuantity,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return StockLevelsCompanion(
      organizationId: organizationId ?? this.organizationId,
      shopId: shopId ?? this.shopId,
      stockLocationId: stockLocationId ?? this.stockLocationId,
      stockIdentity: stockIdentity ?? this.stockIdentity,
      productId: productId ?? this.productId,
      variantId: variantId ?? this.variantId,
      quantity: quantity ?? this.quantity,
      reservedQuantity: reservedQuantity ?? this.reservedQuantity,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (stockLocationId.present) {
      map['stock_location_id'] = Variable<int>(stockLocationId.value);
    }
    if (stockIdentity.present) {
      map['stock_identity'] = Variable<String>(stockIdentity.value);
    }
    if (productId.present) {
      map['product_id'] = Variable<int>(productId.value);
    }
    if (variantId.present) {
      map['variant_id'] = Variable<int>(variantId.value);
    }
    if (quantity.present) {
      map['quantity'] = Variable<double>(quantity.value);
    }
    if (reservedQuantity.present) {
      map['reserved_quantity'] = Variable<double>(reservedQuantity.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('StockLevelsCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('stockLocationId: $stockLocationId, ')
          ..write('stockIdentity: $stockIdentity, ')
          ..write('productId: $productId, ')
          ..write('variantId: $variantId, ')
          ..write('quantity: $quantity, ')
          ..write('reservedQuantity: $reservedQuantity, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CashSessionsTable extends CashSessions
    with TableInfo<$CashSessionsTable, CashSession> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CashSessionsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _terminalIdMeta = const VerificationMeta(
    'terminalId',
  );
  @override
  late final GeneratedColumn<int> terminalId = GeneratedColumn<int>(
    'terminal_id',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _rawJsonMeta = const VerificationMeta(
    'rawJson',
  );
  @override
  late final GeneratedColumn<String> rawJson = GeneratedColumn<String>(
    'raw_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    id,
    shopId,
    terminalId,
    status,
    rawJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'cash_sessions';
  @override
  VerificationContext validateIntegrity(
    Insertable<CashSession> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    }
    if (data.containsKey('terminal_id')) {
      context.handle(
        _terminalIdMeta,
        terminalId.isAcceptableOrUnknown(data['terminal_id']!, _terminalIdMeta),
      );
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('raw_json')) {
      context.handle(
        _rawJsonMeta,
        rawJson.isAcceptableOrUnknown(data['raw_json']!, _rawJsonMeta),
      );
    } else if (isInserting) {
      context.missing(_rawJsonMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, id};
  @override
  CashSession map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return CashSession(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      ),
      terminalId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}terminal_id'],
      ),
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      ),
      rawJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}raw_json'],
      )!,
    );
  }

  @override
  $CashSessionsTable createAlias(String alias) {
    return $CashSessionsTable(attachedDatabase, alias);
  }
}

class CashSession extends DataClass implements Insertable<CashSession> {
  final int organizationId;
  final int id;
  final int? shopId;
  final int? terminalId;
  final String? status;
  final String rawJson;
  const CashSession({
    required this.organizationId,
    required this.id,
    this.shopId,
    this.terminalId,
    this.status,
    required this.rawJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['id'] = Variable<int>(id);
    if (!nullToAbsent || shopId != null) {
      map['shop_id'] = Variable<int>(shopId);
    }
    if (!nullToAbsent || terminalId != null) {
      map['terminal_id'] = Variable<int>(terminalId);
    }
    if (!nullToAbsent || status != null) {
      map['status'] = Variable<String>(status);
    }
    map['raw_json'] = Variable<String>(rawJson);
    return map;
  }

  CashSessionsCompanion toCompanion(bool nullToAbsent) {
    return CashSessionsCompanion(
      organizationId: Value(organizationId),
      id: Value(id),
      shopId: shopId == null && nullToAbsent
          ? const Value.absent()
          : Value(shopId),
      terminalId: terminalId == null && nullToAbsent
          ? const Value.absent()
          : Value(terminalId),
      status: status == null && nullToAbsent
          ? const Value.absent()
          : Value(status),
      rawJson: Value(rawJson),
    );
  }

  factory CashSession.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return CashSession(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      id: serializer.fromJson<int>(json['id']),
      shopId: serializer.fromJson<int?>(json['shopId']),
      terminalId: serializer.fromJson<int?>(json['terminalId']),
      status: serializer.fromJson<String?>(json['status']),
      rawJson: serializer.fromJson<String>(json['rawJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'id': serializer.toJson<int>(id),
      'shopId': serializer.toJson<int?>(shopId),
      'terminalId': serializer.toJson<int?>(terminalId),
      'status': serializer.toJson<String?>(status),
      'rawJson': serializer.toJson<String>(rawJson),
    };
  }

  CashSession copyWith({
    int? organizationId,
    int? id,
    Value<int?> shopId = const Value.absent(),
    Value<int?> terminalId = const Value.absent(),
    Value<String?> status = const Value.absent(),
    String? rawJson,
  }) => CashSession(
    organizationId: organizationId ?? this.organizationId,
    id: id ?? this.id,
    shopId: shopId.present ? shopId.value : this.shopId,
    terminalId: terminalId.present ? terminalId.value : this.terminalId,
    status: status.present ? status.value : this.status,
    rawJson: rawJson ?? this.rawJson,
  );
  CashSession copyWithCompanion(CashSessionsCompanion data) {
    return CashSession(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      id: data.id.present ? data.id.value : this.id,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      terminalId: data.terminalId.present
          ? data.terminalId.value
          : this.terminalId,
      status: data.status.present ? data.status.value : this.status,
      rawJson: data.rawJson.present ? data.rawJson.value : this.rawJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('CashSession(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('terminalId: $terminalId, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(organizationId, id, shopId, terminalId, status, rawJson);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is CashSession &&
          other.organizationId == this.organizationId &&
          other.id == this.id &&
          other.shopId == this.shopId &&
          other.terminalId == this.terminalId &&
          other.status == this.status &&
          other.rawJson == this.rawJson);
}

class CashSessionsCompanion extends UpdateCompanion<CashSession> {
  final Value<int> organizationId;
  final Value<int> id;
  final Value<int?> shopId;
  final Value<int?> terminalId;
  final Value<String?> status;
  final Value<String> rawJson;
  final Value<int> rowid;
  const CashSessionsCompanion({
    this.organizationId = const Value.absent(),
    this.id = const Value.absent(),
    this.shopId = const Value.absent(),
    this.terminalId = const Value.absent(),
    this.status = const Value.absent(),
    this.rawJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  CashSessionsCompanion.insert({
    required int organizationId,
    required int id,
    this.shopId = const Value.absent(),
    this.terminalId = const Value.absent(),
    this.status = const Value.absent(),
    required String rawJson,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       id = Value(id),
       rawJson = Value(rawJson);
  static Insertable<CashSession> custom({
    Expression<int>? organizationId,
    Expression<int>? id,
    Expression<int>? shopId,
    Expression<int>? terminalId,
    Expression<String>? status,
    Expression<String>? rawJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (id != null) 'id': id,
      if (shopId != null) 'shop_id': shopId,
      if (terminalId != null) 'terminal_id': terminalId,
      if (status != null) 'status': status,
      if (rawJson != null) 'raw_json': rawJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  CashSessionsCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? id,
    Value<int?>? shopId,
    Value<int?>? terminalId,
    Value<String?>? status,
    Value<String>? rawJson,
    Value<int>? rowid,
  }) {
    return CashSessionsCompanion(
      organizationId: organizationId ?? this.organizationId,
      id: id ?? this.id,
      shopId: shopId ?? this.shopId,
      terminalId: terminalId ?? this.terminalId,
      status: status ?? this.status,
      rawJson: rawJson ?? this.rawJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (terminalId.present) {
      map['terminal_id'] = Variable<int>(terminalId.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (rawJson.present) {
      map['raw_json'] = Variable<String>(rawJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CashSessionsCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('id: $id, ')
          ..write('shopId: $shopId, ')
          ..write('terminalId: $terminalId, ')
          ..write('status: $status, ')
          ..write('rawJson: $rawJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $SyncMetadataTable extends SyncMetadata
    with TableInfo<$SyncMetadataTable, SyncMetadataData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $SyncMetadataTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _lastCursorMeta = const VerificationMeta(
    'lastCursor',
  );
  @override
  late final GeneratedColumn<int> lastCursor = GeneratedColumn<int>(
    'last_cursor',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _lastSyncAtMeta = const VerificationMeta(
    'lastSyncAt',
  );
  @override
  late final GeneratedColumn<DateTime> lastSyncAt = GeneratedColumn<DateTime>(
    'last_sync_at',
    aliasedName,
    true,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    lastCursor,
    lastSyncAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'sync_metadata';
  @override
  VerificationContext validateIntegrity(
    Insertable<SyncMetadataData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    }
    if (data.containsKey('last_cursor')) {
      context.handle(
        _lastCursorMeta,
        lastCursor.isAcceptableOrUnknown(data['last_cursor']!, _lastCursorMeta),
      );
    }
    if (data.containsKey('last_sync_at')) {
      context.handle(
        _lastSyncAtMeta,
        lastSyncAt.isAcceptableOrUnknown(
          data['last_sync_at']!,
          _lastSyncAtMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId};
  @override
  SyncMetadataData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return SyncMetadataData(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      lastCursor: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}last_cursor'],
      ),
      lastSyncAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}last_sync_at'],
      ),
    );
  }

  @override
  $SyncMetadataTable createAlias(String alias) {
    return $SyncMetadataTable(attachedDatabase, alias);
  }
}

class SyncMetadataData extends DataClass
    implements Insertable<SyncMetadataData> {
  final int organizationId;
  final int? lastCursor;
  final DateTime? lastSyncAt;
  const SyncMetadataData({
    required this.organizationId,
    this.lastCursor,
    this.lastSyncAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    if (!nullToAbsent || lastCursor != null) {
      map['last_cursor'] = Variable<int>(lastCursor);
    }
    if (!nullToAbsent || lastSyncAt != null) {
      map['last_sync_at'] = Variable<DateTime>(lastSyncAt);
    }
    return map;
  }

  SyncMetadataCompanion toCompanion(bool nullToAbsent) {
    return SyncMetadataCompanion(
      organizationId: Value(organizationId),
      lastCursor: lastCursor == null && nullToAbsent
          ? const Value.absent()
          : Value(lastCursor),
      lastSyncAt: lastSyncAt == null && nullToAbsent
          ? const Value.absent()
          : Value(lastSyncAt),
    );
  }

  factory SyncMetadataData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return SyncMetadataData(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      lastCursor: serializer.fromJson<int?>(json['lastCursor']),
      lastSyncAt: serializer.fromJson<DateTime?>(json['lastSyncAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'lastCursor': serializer.toJson<int?>(lastCursor),
      'lastSyncAt': serializer.toJson<DateTime?>(lastSyncAt),
    };
  }

  SyncMetadataData copyWith({
    int? organizationId,
    Value<int?> lastCursor = const Value.absent(),
    Value<DateTime?> lastSyncAt = const Value.absent(),
  }) => SyncMetadataData(
    organizationId: organizationId ?? this.organizationId,
    lastCursor: lastCursor.present ? lastCursor.value : this.lastCursor,
    lastSyncAt: lastSyncAt.present ? lastSyncAt.value : this.lastSyncAt,
  );
  SyncMetadataData copyWithCompanion(SyncMetadataCompanion data) {
    return SyncMetadataData(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      lastCursor: data.lastCursor.present
          ? data.lastCursor.value
          : this.lastCursor,
      lastSyncAt: data.lastSyncAt.present
          ? data.lastSyncAt.value
          : this.lastSyncAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('SyncMetadataData(')
          ..write('organizationId: $organizationId, ')
          ..write('lastCursor: $lastCursor, ')
          ..write('lastSyncAt: $lastSyncAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(organizationId, lastCursor, lastSyncAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is SyncMetadataData &&
          other.organizationId == this.organizationId &&
          other.lastCursor == this.lastCursor &&
          other.lastSyncAt == this.lastSyncAt);
}

class SyncMetadataCompanion extends UpdateCompanion<SyncMetadataData> {
  final Value<int> organizationId;
  final Value<int?> lastCursor;
  final Value<DateTime?> lastSyncAt;
  const SyncMetadataCompanion({
    this.organizationId = const Value.absent(),
    this.lastCursor = const Value.absent(),
    this.lastSyncAt = const Value.absent(),
  });
  SyncMetadataCompanion.insert({
    this.organizationId = const Value.absent(),
    this.lastCursor = const Value.absent(),
    this.lastSyncAt = const Value.absent(),
  });
  static Insertable<SyncMetadataData> custom({
    Expression<int>? organizationId,
    Expression<int>? lastCursor,
    Expression<DateTime>? lastSyncAt,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (lastCursor != null) 'last_cursor': lastCursor,
      if (lastSyncAt != null) 'last_sync_at': lastSyncAt,
    });
  }

  SyncMetadataCompanion copyWith({
    Value<int>? organizationId,
    Value<int?>? lastCursor,
    Value<DateTime?>? lastSyncAt,
  }) {
    return SyncMetadataCompanion(
      organizationId: organizationId ?? this.organizationId,
      lastCursor: lastCursor ?? this.lastCursor,
      lastSyncAt: lastSyncAt ?? this.lastSyncAt,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (lastCursor.present) {
      map['last_cursor'] = Variable<int>(lastCursor.value);
    }
    if (lastSyncAt.present) {
      map['last_sync_at'] = Variable<DateTime>(lastSyncAt.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('SyncMetadataCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('lastCursor: $lastCursor, ')
          ..write('lastSyncAt: $lastSyncAt')
          ..write(')'))
        .toString();
  }
}

class $BootstrapMetadataTable extends BootstrapMetadata
    with TableInfo<$BootstrapMetadataTable, BootstrapMetadataData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $BootstrapMetadataTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _lastBootstrapAtMeta = const VerificationMeta(
    'lastBootstrapAt',
  );
  @override
  late final GeneratedColumn<DateTime> lastBootstrapAt =
      GeneratedColumn<DateTime>(
        'last_bootstrap_at',
        aliasedName,
        false,
        type: DriftSqlType.dateTime,
        requiredDuringInsert: true,
      );
  @override
  List<GeneratedColumn> get $columns => [
    organizationId,
    shopId,
    lastBootstrapAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'bootstrap_metadata';
  @override
  VerificationContext validateIntegrity(
    Insertable<BootstrapMetadataData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    } else if (isInserting) {
      context.missing(_shopIdMeta);
    }
    if (data.containsKey('last_bootstrap_at')) {
      context.handle(
        _lastBootstrapAtMeta,
        lastBootstrapAt.isAcceptableOrUnknown(
          data['last_bootstrap_at']!,
          _lastBootstrapAtMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_lastBootstrapAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {organizationId, shopId};
  @override
  BootstrapMetadataData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return BootstrapMetadataData(
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      )!,
      lastBootstrapAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}last_bootstrap_at'],
      )!,
    );
  }

  @override
  $BootstrapMetadataTable createAlias(String alias) {
    return $BootstrapMetadataTable(attachedDatabase, alias);
  }
}

class BootstrapMetadataData extends DataClass
    implements Insertable<BootstrapMetadataData> {
  final int organizationId;
  final int shopId;
  final DateTime lastBootstrapAt;
  const BootstrapMetadataData({
    required this.organizationId,
    required this.shopId,
    required this.lastBootstrapAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['organization_id'] = Variable<int>(organizationId);
    map['shop_id'] = Variable<int>(shopId);
    map['last_bootstrap_at'] = Variable<DateTime>(lastBootstrapAt);
    return map;
  }

  BootstrapMetadataCompanion toCompanion(bool nullToAbsent) {
    return BootstrapMetadataCompanion(
      organizationId: Value(organizationId),
      shopId: Value(shopId),
      lastBootstrapAt: Value(lastBootstrapAt),
    );
  }

  factory BootstrapMetadataData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return BootstrapMetadataData(
      organizationId: serializer.fromJson<int>(json['organizationId']),
      shopId: serializer.fromJson<int>(json['shopId']),
      lastBootstrapAt: serializer.fromJson<DateTime>(json['lastBootstrapAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'organizationId': serializer.toJson<int>(organizationId),
      'shopId': serializer.toJson<int>(shopId),
      'lastBootstrapAt': serializer.toJson<DateTime>(lastBootstrapAt),
    };
  }

  BootstrapMetadataData copyWith({
    int? organizationId,
    int? shopId,
    DateTime? lastBootstrapAt,
  }) => BootstrapMetadataData(
    organizationId: organizationId ?? this.organizationId,
    shopId: shopId ?? this.shopId,
    lastBootstrapAt: lastBootstrapAt ?? this.lastBootstrapAt,
  );
  BootstrapMetadataData copyWithCompanion(BootstrapMetadataCompanion data) {
    return BootstrapMetadataData(
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      lastBootstrapAt: data.lastBootstrapAt.present
          ? data.lastBootstrapAt.value
          : this.lastBootstrapAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('BootstrapMetadataData(')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('lastBootstrapAt: $lastBootstrapAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(organizationId, shopId, lastBootstrapAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is BootstrapMetadataData &&
          other.organizationId == this.organizationId &&
          other.shopId == this.shopId &&
          other.lastBootstrapAt == this.lastBootstrapAt);
}

class BootstrapMetadataCompanion
    extends UpdateCompanion<BootstrapMetadataData> {
  final Value<int> organizationId;
  final Value<int> shopId;
  final Value<DateTime> lastBootstrapAt;
  final Value<int> rowid;
  const BootstrapMetadataCompanion({
    this.organizationId = const Value.absent(),
    this.shopId = const Value.absent(),
    this.lastBootstrapAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  BootstrapMetadataCompanion.insert({
    required int organizationId,
    required int shopId,
    required DateTime lastBootstrapAt,
    this.rowid = const Value.absent(),
  }) : organizationId = Value(organizationId),
       shopId = Value(shopId),
       lastBootstrapAt = Value(lastBootstrapAt);
  static Insertable<BootstrapMetadataData> custom({
    Expression<int>? organizationId,
    Expression<int>? shopId,
    Expression<DateTime>? lastBootstrapAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (organizationId != null) 'organization_id': organizationId,
      if (shopId != null) 'shop_id': shopId,
      if (lastBootstrapAt != null) 'last_bootstrap_at': lastBootstrapAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  BootstrapMetadataCompanion copyWith({
    Value<int>? organizationId,
    Value<int>? shopId,
    Value<DateTime>? lastBootstrapAt,
    Value<int>? rowid,
  }) {
    return BootstrapMetadataCompanion(
      organizationId: organizationId ?? this.organizationId,
      shopId: shopId ?? this.shopId,
      lastBootstrapAt: lastBootstrapAt ?? this.lastBootstrapAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (lastBootstrapAt.present) {
      map['last_bootstrap_at'] = Variable<DateTime>(lastBootstrapAt.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('BootstrapMetadataCompanion(')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('lastBootstrapAt: $lastBootstrapAt, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $SyncOutboxTable extends SyncOutbox
    with TableInfo<$SyncOutboxTable, SyncOutboxData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $SyncOutboxTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    hasAutoIncrement: true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'PRIMARY KEY AUTOINCREMENT',
    ),
  );
  static const VerificationMeta _organizationIdMeta = const VerificationMeta(
    'organizationId',
  );
  @override
  late final GeneratedColumn<int> organizationId = GeneratedColumn<int>(
    'organization_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _shopIdMeta = const VerificationMeta('shopId');
  @override
  late final GeneratedColumn<int> shopId = GeneratedColumn<int>(
    'shop_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _deviceIdMeta = const VerificationMeta(
    'deviceId',
  );
  @override
  late final GeneratedColumn<int> deviceId = GeneratedColumn<int>(
    'device_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _eventUuidMeta = const VerificationMeta(
    'eventUuid',
  );
  @override
  late final GeneratedColumn<String> eventUuid = GeneratedColumn<String>(
    'event_uuid',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _entityTypeMeta = const VerificationMeta(
    'entityType',
  );
  @override
  late final GeneratedColumn<String> entityType = GeneratedColumn<String>(
    'entity_type',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _entityIdMeta = const VerificationMeta(
    'entityId',
  );
  @override
  late final GeneratedColumn<String> entityId = GeneratedColumn<String>(
    'entity_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _actionMeta = const VerificationMeta('action');
  @override
  late final GeneratedColumn<String> action = GeneratedColumn<String>(
    'action',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadJsonMeta = const VerificationMeta(
    'payloadJson',
  );
  @override
  late final GeneratedColumn<String> payloadJson = GeneratedColumn<String>(
    'payload_json',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _occurredAtMeta = const VerificationMeta(
    'occurredAt',
  );
  @override
  late final GeneratedColumn<DateTime> occurredAt = GeneratedColumn<DateTime>(
    'occurred_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('queued'),
  );
  static const VerificationMeta _attemptCountMeta = const VerificationMeta(
    'attemptCount',
  );
  @override
  late final GeneratedColumn<int> attemptCount = GeneratedColumn<int>(
    'attempt_count',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _lastAttemptAtMeta = const VerificationMeta(
    'lastAttemptAt',
  );
  @override
  late final GeneratedColumn<DateTime> lastAttemptAt =
      GeneratedColumn<DateTime>(
        'last_attempt_at',
        aliasedName,
        true,
        type: DriftSqlType.dateTime,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _lastErrorMeta = const VerificationMeta(
    'lastError',
  );
  @override
  late final GeneratedColumn<String> lastError = GeneratedColumn<String>(
    'last_error',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _serverResultJsonMeta = const VerificationMeta(
    'serverResultJson',
  );
  @override
  late final GeneratedColumn<String> serverResultJson = GeneratedColumn<String>(
    'server_result_json',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _failureKindMeta = const VerificationMeta(
    'failureKind',
  );
  @override
  late final GeneratedColumn<String> failureKind = GeneratedColumn<String>(
    'failure_kind',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _httpStatusCodeMeta = const VerificationMeta(
    'httpStatusCode',
  );
  @override
  late final GeneratedColumn<int> httpStatusCode = GeneratedColumn<int>(
    'http_status_code',
    aliasedName,
    true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _createdAtMeta = const VerificationMeta(
    'createdAt',
  );
  @override
  late final GeneratedColumn<DateTime> createdAt = GeneratedColumn<DateTime>(
    'created_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    organizationId,
    shopId,
    deviceId,
    eventUuid,
    entityType,
    entityId,
    action,
    payloadJson,
    occurredAt,
    status,
    attemptCount,
    lastAttemptAt,
    lastError,
    serverResultJson,
    failureKind,
    httpStatusCode,
    createdAt,
    updatedAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'sync_outbox';
  @override
  VerificationContext validateIntegrity(
    Insertable<SyncOutboxData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('organization_id')) {
      context.handle(
        _organizationIdMeta,
        organizationId.isAcceptableOrUnknown(
          data['organization_id']!,
          _organizationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_organizationIdMeta);
    }
    if (data.containsKey('shop_id')) {
      context.handle(
        _shopIdMeta,
        shopId.isAcceptableOrUnknown(data['shop_id']!, _shopIdMeta),
      );
    } else if (isInserting) {
      context.missing(_shopIdMeta);
    }
    if (data.containsKey('device_id')) {
      context.handle(
        _deviceIdMeta,
        deviceId.isAcceptableOrUnknown(data['device_id']!, _deviceIdMeta),
      );
    } else if (isInserting) {
      context.missing(_deviceIdMeta);
    }
    if (data.containsKey('event_uuid')) {
      context.handle(
        _eventUuidMeta,
        eventUuid.isAcceptableOrUnknown(data['event_uuid']!, _eventUuidMeta),
      );
    } else if (isInserting) {
      context.missing(_eventUuidMeta);
    }
    if (data.containsKey('entity_type')) {
      context.handle(
        _entityTypeMeta,
        entityType.isAcceptableOrUnknown(data['entity_type']!, _entityTypeMeta),
      );
    } else if (isInserting) {
      context.missing(_entityTypeMeta);
    }
    if (data.containsKey('entity_id')) {
      context.handle(
        _entityIdMeta,
        entityId.isAcceptableOrUnknown(data['entity_id']!, _entityIdMeta),
      );
    } else if (isInserting) {
      context.missing(_entityIdMeta);
    }
    if (data.containsKey('action')) {
      context.handle(
        _actionMeta,
        action.isAcceptableOrUnknown(data['action']!, _actionMeta),
      );
    } else if (isInserting) {
      context.missing(_actionMeta);
    }
    if (data.containsKey('payload_json')) {
      context.handle(
        _payloadJsonMeta,
        payloadJson.isAcceptableOrUnknown(
          data['payload_json']!,
          _payloadJsonMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadJsonMeta);
    }
    if (data.containsKey('occurred_at')) {
      context.handle(
        _occurredAtMeta,
        occurredAt.isAcceptableOrUnknown(data['occurred_at']!, _occurredAtMeta),
      );
    } else if (isInserting) {
      context.missing(_occurredAtMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('attempt_count')) {
      context.handle(
        _attemptCountMeta,
        attemptCount.isAcceptableOrUnknown(
          data['attempt_count']!,
          _attemptCountMeta,
        ),
      );
    }
    if (data.containsKey('last_attempt_at')) {
      context.handle(
        _lastAttemptAtMeta,
        lastAttemptAt.isAcceptableOrUnknown(
          data['last_attempt_at']!,
          _lastAttemptAtMeta,
        ),
      );
    }
    if (data.containsKey('last_error')) {
      context.handle(
        _lastErrorMeta,
        lastError.isAcceptableOrUnknown(data['last_error']!, _lastErrorMeta),
      );
    }
    if (data.containsKey('server_result_json')) {
      context.handle(
        _serverResultJsonMeta,
        serverResultJson.isAcceptableOrUnknown(
          data['server_result_json']!,
          _serverResultJsonMeta,
        ),
      );
    }
    if (data.containsKey('failure_kind')) {
      context.handle(
        _failureKindMeta,
        failureKind.isAcceptableOrUnknown(
          data['failure_kind']!,
          _failureKindMeta,
        ),
      );
    }
    if (data.containsKey('http_status_code')) {
      context.handle(
        _httpStatusCodeMeta,
        httpStatusCode.isAcceptableOrUnknown(
          data['http_status_code']!,
          _httpStatusCodeMeta,
        ),
      );
    }
    if (data.containsKey('created_at')) {
      context.handle(
        _createdAtMeta,
        createdAt.isAcceptableOrUnknown(data['created_at']!, _createdAtMeta),
      );
    } else if (isInserting) {
      context.missing(_createdAtMeta);
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  List<Set<GeneratedColumn>> get uniqueKeys => [
    {organizationId, eventUuid},
  ];
  @override
  SyncOutboxData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return SyncOutboxData(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      organizationId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}organization_id'],
      )!,
      shopId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}shop_id'],
      )!,
      deviceId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}device_id'],
      )!,
      eventUuid: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}event_uuid'],
      )!,
      entityType: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}entity_type'],
      )!,
      entityId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}entity_id'],
      )!,
      action: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}action'],
      )!,
      payloadJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_json'],
      )!,
      occurredAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}occurred_at'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      attemptCount: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}attempt_count'],
      )!,
      lastAttemptAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}last_attempt_at'],
      ),
      lastError: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}last_error'],
      ),
      serverResultJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}server_result_json'],
      ),
      failureKind: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}failure_kind'],
      ),
      httpStatusCode: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}http_status_code'],
      ),
      createdAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}created_at'],
      )!,
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $SyncOutboxTable createAlias(String alias) {
    return $SyncOutboxTable(attachedDatabase, alias);
  }
}

class SyncOutboxData extends DataClass implements Insertable<SyncOutboxData> {
  final int id;
  final int organizationId;
  final int shopId;
  final int deviceId;
  final String eventUuid;
  final String entityType;
  final String entityId;
  final String action;
  final String payloadJson;
  final DateTime occurredAt;
  final String status;
  final int attemptCount;
  final DateTime? lastAttemptAt;
  final String? lastError;
  final String? serverResultJson;
  final String? failureKind;
  final int? httpStatusCode;
  final DateTime createdAt;
  final DateTime updatedAt;
  const SyncOutboxData({
    required this.id,
    required this.organizationId,
    required this.shopId,
    required this.deviceId,
    required this.eventUuid,
    required this.entityType,
    required this.entityId,
    required this.action,
    required this.payloadJson,
    required this.occurredAt,
    required this.status,
    required this.attemptCount,
    this.lastAttemptAt,
    this.lastError,
    this.serverResultJson,
    this.failureKind,
    this.httpStatusCode,
    required this.createdAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['organization_id'] = Variable<int>(organizationId);
    map['shop_id'] = Variable<int>(shopId);
    map['device_id'] = Variable<int>(deviceId);
    map['event_uuid'] = Variable<String>(eventUuid);
    map['entity_type'] = Variable<String>(entityType);
    map['entity_id'] = Variable<String>(entityId);
    map['action'] = Variable<String>(action);
    map['payload_json'] = Variable<String>(payloadJson);
    map['occurred_at'] = Variable<DateTime>(occurredAt);
    map['status'] = Variable<String>(status);
    map['attempt_count'] = Variable<int>(attemptCount);
    if (!nullToAbsent || lastAttemptAt != null) {
      map['last_attempt_at'] = Variable<DateTime>(lastAttemptAt);
    }
    if (!nullToAbsent || lastError != null) {
      map['last_error'] = Variable<String>(lastError);
    }
    if (!nullToAbsent || serverResultJson != null) {
      map['server_result_json'] = Variable<String>(serverResultJson);
    }
    if (!nullToAbsent || failureKind != null) {
      map['failure_kind'] = Variable<String>(failureKind);
    }
    if (!nullToAbsent || httpStatusCode != null) {
      map['http_status_code'] = Variable<int>(httpStatusCode);
    }
    map['created_at'] = Variable<DateTime>(createdAt);
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  SyncOutboxCompanion toCompanion(bool nullToAbsent) {
    return SyncOutboxCompanion(
      id: Value(id),
      organizationId: Value(organizationId),
      shopId: Value(shopId),
      deviceId: Value(deviceId),
      eventUuid: Value(eventUuid),
      entityType: Value(entityType),
      entityId: Value(entityId),
      action: Value(action),
      payloadJson: Value(payloadJson),
      occurredAt: Value(occurredAt),
      status: Value(status),
      attemptCount: Value(attemptCount),
      lastAttemptAt: lastAttemptAt == null && nullToAbsent
          ? const Value.absent()
          : Value(lastAttemptAt),
      lastError: lastError == null && nullToAbsent
          ? const Value.absent()
          : Value(lastError),
      serverResultJson: serverResultJson == null && nullToAbsent
          ? const Value.absent()
          : Value(serverResultJson),
      failureKind: failureKind == null && nullToAbsent
          ? const Value.absent()
          : Value(failureKind),
      httpStatusCode: httpStatusCode == null && nullToAbsent
          ? const Value.absent()
          : Value(httpStatusCode),
      createdAt: Value(createdAt),
      updatedAt: Value(updatedAt),
    );
  }

  factory SyncOutboxData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return SyncOutboxData(
      id: serializer.fromJson<int>(json['id']),
      organizationId: serializer.fromJson<int>(json['organizationId']),
      shopId: serializer.fromJson<int>(json['shopId']),
      deviceId: serializer.fromJson<int>(json['deviceId']),
      eventUuid: serializer.fromJson<String>(json['eventUuid']),
      entityType: serializer.fromJson<String>(json['entityType']),
      entityId: serializer.fromJson<String>(json['entityId']),
      action: serializer.fromJson<String>(json['action']),
      payloadJson: serializer.fromJson<String>(json['payloadJson']),
      occurredAt: serializer.fromJson<DateTime>(json['occurredAt']),
      status: serializer.fromJson<String>(json['status']),
      attemptCount: serializer.fromJson<int>(json['attemptCount']),
      lastAttemptAt: serializer.fromJson<DateTime?>(json['lastAttemptAt']),
      lastError: serializer.fromJson<String?>(json['lastError']),
      serverResultJson: serializer.fromJson<String?>(json['serverResultJson']),
      failureKind: serializer.fromJson<String?>(json['failureKind']),
      httpStatusCode: serializer.fromJson<int?>(json['httpStatusCode']),
      createdAt: serializer.fromJson<DateTime>(json['createdAt']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'organizationId': serializer.toJson<int>(organizationId),
      'shopId': serializer.toJson<int>(shopId),
      'deviceId': serializer.toJson<int>(deviceId),
      'eventUuid': serializer.toJson<String>(eventUuid),
      'entityType': serializer.toJson<String>(entityType),
      'entityId': serializer.toJson<String>(entityId),
      'action': serializer.toJson<String>(action),
      'payloadJson': serializer.toJson<String>(payloadJson),
      'occurredAt': serializer.toJson<DateTime>(occurredAt),
      'status': serializer.toJson<String>(status),
      'attemptCount': serializer.toJson<int>(attemptCount),
      'lastAttemptAt': serializer.toJson<DateTime?>(lastAttemptAt),
      'lastError': serializer.toJson<String?>(lastError),
      'serverResultJson': serializer.toJson<String?>(serverResultJson),
      'failureKind': serializer.toJson<String?>(failureKind),
      'httpStatusCode': serializer.toJson<int?>(httpStatusCode),
      'createdAt': serializer.toJson<DateTime>(createdAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  SyncOutboxData copyWith({
    int? id,
    int? organizationId,
    int? shopId,
    int? deviceId,
    String? eventUuid,
    String? entityType,
    String? entityId,
    String? action,
    String? payloadJson,
    DateTime? occurredAt,
    String? status,
    int? attemptCount,
    Value<DateTime?> lastAttemptAt = const Value.absent(),
    Value<String?> lastError = const Value.absent(),
    Value<String?> serverResultJson = const Value.absent(),
    Value<String?> failureKind = const Value.absent(),
    Value<int?> httpStatusCode = const Value.absent(),
    DateTime? createdAt,
    DateTime? updatedAt,
  }) => SyncOutboxData(
    id: id ?? this.id,
    organizationId: organizationId ?? this.organizationId,
    shopId: shopId ?? this.shopId,
    deviceId: deviceId ?? this.deviceId,
    eventUuid: eventUuid ?? this.eventUuid,
    entityType: entityType ?? this.entityType,
    entityId: entityId ?? this.entityId,
    action: action ?? this.action,
    payloadJson: payloadJson ?? this.payloadJson,
    occurredAt: occurredAt ?? this.occurredAt,
    status: status ?? this.status,
    attemptCount: attemptCount ?? this.attemptCount,
    lastAttemptAt: lastAttemptAt.present
        ? lastAttemptAt.value
        : this.lastAttemptAt,
    lastError: lastError.present ? lastError.value : this.lastError,
    serverResultJson: serverResultJson.present
        ? serverResultJson.value
        : this.serverResultJson,
    failureKind: failureKind.present ? failureKind.value : this.failureKind,
    httpStatusCode: httpStatusCode.present
        ? httpStatusCode.value
        : this.httpStatusCode,
    createdAt: createdAt ?? this.createdAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  SyncOutboxData copyWithCompanion(SyncOutboxCompanion data) {
    return SyncOutboxData(
      id: data.id.present ? data.id.value : this.id,
      organizationId: data.organizationId.present
          ? data.organizationId.value
          : this.organizationId,
      shopId: data.shopId.present ? data.shopId.value : this.shopId,
      deviceId: data.deviceId.present ? data.deviceId.value : this.deviceId,
      eventUuid: data.eventUuid.present ? data.eventUuid.value : this.eventUuid,
      entityType: data.entityType.present
          ? data.entityType.value
          : this.entityType,
      entityId: data.entityId.present ? data.entityId.value : this.entityId,
      action: data.action.present ? data.action.value : this.action,
      payloadJson: data.payloadJson.present
          ? data.payloadJson.value
          : this.payloadJson,
      occurredAt: data.occurredAt.present
          ? data.occurredAt.value
          : this.occurredAt,
      status: data.status.present ? data.status.value : this.status,
      attemptCount: data.attemptCount.present
          ? data.attemptCount.value
          : this.attemptCount,
      lastAttemptAt: data.lastAttemptAt.present
          ? data.lastAttemptAt.value
          : this.lastAttemptAt,
      lastError: data.lastError.present ? data.lastError.value : this.lastError,
      serverResultJson: data.serverResultJson.present
          ? data.serverResultJson.value
          : this.serverResultJson,
      failureKind: data.failureKind.present
          ? data.failureKind.value
          : this.failureKind,
      httpStatusCode: data.httpStatusCode.present
          ? data.httpStatusCode.value
          : this.httpStatusCode,
      createdAt: data.createdAt.present ? data.createdAt.value : this.createdAt,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('SyncOutboxData(')
          ..write('id: $id, ')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('deviceId: $deviceId, ')
          ..write('eventUuid: $eventUuid, ')
          ..write('entityType: $entityType, ')
          ..write('entityId: $entityId, ')
          ..write('action: $action, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('occurredAt: $occurredAt, ')
          ..write('status: $status, ')
          ..write('attemptCount: $attemptCount, ')
          ..write('lastAttemptAt: $lastAttemptAt, ')
          ..write('lastError: $lastError, ')
          ..write('serverResultJson: $serverResultJson, ')
          ..write('failureKind: $failureKind, ')
          ..write('httpStatusCode: $httpStatusCode, ')
          ..write('createdAt: $createdAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    organizationId,
    shopId,
    deviceId,
    eventUuid,
    entityType,
    entityId,
    action,
    payloadJson,
    occurredAt,
    status,
    attemptCount,
    lastAttemptAt,
    lastError,
    serverResultJson,
    failureKind,
    httpStatusCode,
    createdAt,
    updatedAt,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is SyncOutboxData &&
          other.id == this.id &&
          other.organizationId == this.organizationId &&
          other.shopId == this.shopId &&
          other.deviceId == this.deviceId &&
          other.eventUuid == this.eventUuid &&
          other.entityType == this.entityType &&
          other.entityId == this.entityId &&
          other.action == this.action &&
          other.payloadJson == this.payloadJson &&
          other.occurredAt == this.occurredAt &&
          other.status == this.status &&
          other.attemptCount == this.attemptCount &&
          other.lastAttemptAt == this.lastAttemptAt &&
          other.lastError == this.lastError &&
          other.serverResultJson == this.serverResultJson &&
          other.failureKind == this.failureKind &&
          other.httpStatusCode == this.httpStatusCode &&
          other.createdAt == this.createdAt &&
          other.updatedAt == this.updatedAt);
}

class SyncOutboxCompanion extends UpdateCompanion<SyncOutboxData> {
  final Value<int> id;
  final Value<int> organizationId;
  final Value<int> shopId;
  final Value<int> deviceId;
  final Value<String> eventUuid;
  final Value<String> entityType;
  final Value<String> entityId;
  final Value<String> action;
  final Value<String> payloadJson;
  final Value<DateTime> occurredAt;
  final Value<String> status;
  final Value<int> attemptCount;
  final Value<DateTime?> lastAttemptAt;
  final Value<String?> lastError;
  final Value<String?> serverResultJson;
  final Value<String?> failureKind;
  final Value<int?> httpStatusCode;
  final Value<DateTime> createdAt;
  final Value<DateTime> updatedAt;
  const SyncOutboxCompanion({
    this.id = const Value.absent(),
    this.organizationId = const Value.absent(),
    this.shopId = const Value.absent(),
    this.deviceId = const Value.absent(),
    this.eventUuid = const Value.absent(),
    this.entityType = const Value.absent(),
    this.entityId = const Value.absent(),
    this.action = const Value.absent(),
    this.payloadJson = const Value.absent(),
    this.occurredAt = const Value.absent(),
    this.status = const Value.absent(),
    this.attemptCount = const Value.absent(),
    this.lastAttemptAt = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverResultJson = const Value.absent(),
    this.failureKind = const Value.absent(),
    this.httpStatusCode = const Value.absent(),
    this.createdAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
  });
  SyncOutboxCompanion.insert({
    this.id = const Value.absent(),
    required int organizationId,
    required int shopId,
    required int deviceId,
    required String eventUuid,
    required String entityType,
    required String entityId,
    required String action,
    required String payloadJson,
    required DateTime occurredAt,
    this.status = const Value.absent(),
    this.attemptCount = const Value.absent(),
    this.lastAttemptAt = const Value.absent(),
    this.lastError = const Value.absent(),
    this.serverResultJson = const Value.absent(),
    this.failureKind = const Value.absent(),
    this.httpStatusCode = const Value.absent(),
    required DateTime createdAt,
    required DateTime updatedAt,
  }) : organizationId = Value(organizationId),
       shopId = Value(shopId),
       deviceId = Value(deviceId),
       eventUuid = Value(eventUuid),
       entityType = Value(entityType),
       entityId = Value(entityId),
       action = Value(action),
       payloadJson = Value(payloadJson),
       occurredAt = Value(occurredAt),
       createdAt = Value(createdAt),
       updatedAt = Value(updatedAt);
  static Insertable<SyncOutboxData> custom({
    Expression<int>? id,
    Expression<int>? organizationId,
    Expression<int>? shopId,
    Expression<int>? deviceId,
    Expression<String>? eventUuid,
    Expression<String>? entityType,
    Expression<String>? entityId,
    Expression<String>? action,
    Expression<String>? payloadJson,
    Expression<DateTime>? occurredAt,
    Expression<String>? status,
    Expression<int>? attemptCount,
    Expression<DateTime>? lastAttemptAt,
    Expression<String>? lastError,
    Expression<String>? serverResultJson,
    Expression<String>? failureKind,
    Expression<int>? httpStatusCode,
    Expression<DateTime>? createdAt,
    Expression<DateTime>? updatedAt,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (organizationId != null) 'organization_id': organizationId,
      if (shopId != null) 'shop_id': shopId,
      if (deviceId != null) 'device_id': deviceId,
      if (eventUuid != null) 'event_uuid': eventUuid,
      if (entityType != null) 'entity_type': entityType,
      if (entityId != null) 'entity_id': entityId,
      if (action != null) 'action': action,
      if (payloadJson != null) 'payload_json': payloadJson,
      if (occurredAt != null) 'occurred_at': occurredAt,
      if (status != null) 'status': status,
      if (attemptCount != null) 'attempt_count': attemptCount,
      if (lastAttemptAt != null) 'last_attempt_at': lastAttemptAt,
      if (lastError != null) 'last_error': lastError,
      if (serverResultJson != null) 'server_result_json': serverResultJson,
      if (failureKind != null) 'failure_kind': failureKind,
      if (httpStatusCode != null) 'http_status_code': httpStatusCode,
      if (createdAt != null) 'created_at': createdAt,
      if (updatedAt != null) 'updated_at': updatedAt,
    });
  }

  SyncOutboxCompanion copyWith({
    Value<int>? id,
    Value<int>? organizationId,
    Value<int>? shopId,
    Value<int>? deviceId,
    Value<String>? eventUuid,
    Value<String>? entityType,
    Value<String>? entityId,
    Value<String>? action,
    Value<String>? payloadJson,
    Value<DateTime>? occurredAt,
    Value<String>? status,
    Value<int>? attemptCount,
    Value<DateTime?>? lastAttemptAt,
    Value<String?>? lastError,
    Value<String?>? serverResultJson,
    Value<String?>? failureKind,
    Value<int?>? httpStatusCode,
    Value<DateTime>? createdAt,
    Value<DateTime>? updatedAt,
  }) {
    return SyncOutboxCompanion(
      id: id ?? this.id,
      organizationId: organizationId ?? this.organizationId,
      shopId: shopId ?? this.shopId,
      deviceId: deviceId ?? this.deviceId,
      eventUuid: eventUuid ?? this.eventUuid,
      entityType: entityType ?? this.entityType,
      entityId: entityId ?? this.entityId,
      action: action ?? this.action,
      payloadJson: payloadJson ?? this.payloadJson,
      occurredAt: occurredAt ?? this.occurredAt,
      status: status ?? this.status,
      attemptCount: attemptCount ?? this.attemptCount,
      lastAttemptAt: lastAttemptAt ?? this.lastAttemptAt,
      lastError: lastError ?? this.lastError,
      serverResultJson: serverResultJson ?? this.serverResultJson,
      failureKind: failureKind ?? this.failureKind,
      httpStatusCode: httpStatusCode ?? this.httpStatusCode,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (organizationId.present) {
      map['organization_id'] = Variable<int>(organizationId.value);
    }
    if (shopId.present) {
      map['shop_id'] = Variable<int>(shopId.value);
    }
    if (deviceId.present) {
      map['device_id'] = Variable<int>(deviceId.value);
    }
    if (eventUuid.present) {
      map['event_uuid'] = Variable<String>(eventUuid.value);
    }
    if (entityType.present) {
      map['entity_type'] = Variable<String>(entityType.value);
    }
    if (entityId.present) {
      map['entity_id'] = Variable<String>(entityId.value);
    }
    if (action.present) {
      map['action'] = Variable<String>(action.value);
    }
    if (payloadJson.present) {
      map['payload_json'] = Variable<String>(payloadJson.value);
    }
    if (occurredAt.present) {
      map['occurred_at'] = Variable<DateTime>(occurredAt.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (attemptCount.present) {
      map['attempt_count'] = Variable<int>(attemptCount.value);
    }
    if (lastAttemptAt.present) {
      map['last_attempt_at'] = Variable<DateTime>(lastAttemptAt.value);
    }
    if (lastError.present) {
      map['last_error'] = Variable<String>(lastError.value);
    }
    if (serverResultJson.present) {
      map['server_result_json'] = Variable<String>(serverResultJson.value);
    }
    if (failureKind.present) {
      map['failure_kind'] = Variable<String>(failureKind.value);
    }
    if (httpStatusCode.present) {
      map['http_status_code'] = Variable<int>(httpStatusCode.value);
    }
    if (createdAt.present) {
      map['created_at'] = Variable<DateTime>(createdAt.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('SyncOutboxCompanion(')
          ..write('id: $id, ')
          ..write('organizationId: $organizationId, ')
          ..write('shopId: $shopId, ')
          ..write('deviceId: $deviceId, ')
          ..write('eventUuid: $eventUuid, ')
          ..write('entityType: $entityType, ')
          ..write('entityId: $entityId, ')
          ..write('action: $action, ')
          ..write('payloadJson: $payloadJson, ')
          ..write('occurredAt: $occurredAt, ')
          ..write('status: $status, ')
          ..write('attemptCount: $attemptCount, ')
          ..write('lastAttemptAt: $lastAttemptAt, ')
          ..write('lastError: $lastError, ')
          ..write('serverResultJson: $serverResultJson, ')
          ..write('failureKind: $failureKind, ')
          ..write('httpStatusCode: $httpStatusCode, ')
          ..write('createdAt: $createdAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }
}

abstract class _$AppDatabase extends GeneratedDatabase {
  _$AppDatabase(QueryExecutor e) : super(e);
  $AppDatabaseManager get managers => $AppDatabaseManager(this);
  late final $OrganizationsCacheTable organizationsCache =
      $OrganizationsCacheTable(this);
  late final $EntitlementsCacheTable entitlementsCache =
      $EntitlementsCacheTable(this);
  late final $CategoriesTable categories = $CategoriesTable(this);
  late final $ProductsTable products = $ProductsTable(this);
  late final $ProductVariantsTable productVariants = $ProductVariantsTable(
    this,
  );
  late final $CustomersTable customers = $CustomersTable(this);
  late final $StockLevelsTable stockLevels = $StockLevelsTable(this);
  late final $CashSessionsTable cashSessions = $CashSessionsTable(this);
  late final $SyncMetadataTable syncMetadata = $SyncMetadataTable(this);
  late final $BootstrapMetadataTable bootstrapMetadata =
      $BootstrapMetadataTable(this);
  late final $SyncOutboxTable syncOutbox = $SyncOutboxTable(this);
  @override
  Iterable<TableInfo<Table, Object?>> get allTables =>
      allSchemaEntities.whereType<TableInfo<Table, Object?>>();
  @override
  List<DatabaseSchemaEntity> get allSchemaEntities => [
    organizationsCache,
    entitlementsCache,
    categories,
    products,
    productVariants,
    customers,
    stockLevels,
    cashSessions,
    syncMetadata,
    bootstrapMetadata,
    syncOutbox,
  ];
}

typedef $$OrganizationsCacheTableCreateCompanionBuilder =
    OrganizationsCacheCompanion Function({
      Value<int> organizationId,
      required String name,
    });
typedef $$OrganizationsCacheTableUpdateCompanionBuilder =
    OrganizationsCacheCompanion Function({
      Value<int> organizationId,
      Value<String> name,
    });

class $$OrganizationsCacheTableFilterComposer
    extends Composer<_$AppDatabase, $OrganizationsCacheTable> {
  $$OrganizationsCacheTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );
}

class $$OrganizationsCacheTableOrderingComposer
    extends Composer<_$AppDatabase, $OrganizationsCacheTable> {
  $$OrganizationsCacheTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$OrganizationsCacheTableAnnotationComposer
    extends Composer<_$AppDatabase, $OrganizationsCacheTable> {
  $$OrganizationsCacheTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);
}

class $$OrganizationsCacheTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $OrganizationsCacheTable,
          OrganizationsCacheData,
          $$OrganizationsCacheTableFilterComposer,
          $$OrganizationsCacheTableOrderingComposer,
          $$OrganizationsCacheTableAnnotationComposer,
          $$OrganizationsCacheTableCreateCompanionBuilder,
          $$OrganizationsCacheTableUpdateCompanionBuilder,
          (
            OrganizationsCacheData,
            BaseReferences<
              _$AppDatabase,
              $OrganizationsCacheTable,
              OrganizationsCacheData
            >,
          ),
          OrganizationsCacheData,
          PrefetchHooks Function()
        > {
  $$OrganizationsCacheTableTableManager(
    _$AppDatabase db,
    $OrganizationsCacheTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$OrganizationsCacheTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$OrganizationsCacheTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$OrganizationsCacheTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<String> name = const Value.absent(),
              }) => OrganizationsCacheCompanion(
                organizationId: organizationId,
                name: name,
              ),
          createCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                required String name,
              }) => OrganizationsCacheCompanion.insert(
                organizationId: organizationId,
                name: name,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$OrganizationsCacheTable, OrganizationsCacheData>(
                    table,
                  ),
                  BaseReferences<
                    _$AppDatabase,
                    $OrganizationsCacheTable,
                    OrganizationsCacheData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$OrganizationsCacheTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $OrganizationsCacheTable,
      OrganizationsCacheData,
      $$OrganizationsCacheTableFilterComposer,
      $$OrganizationsCacheTableOrderingComposer,
      $$OrganizationsCacheTableAnnotationComposer,
      $$OrganizationsCacheTableCreateCompanionBuilder,
      $$OrganizationsCacheTableUpdateCompanionBuilder,
      (
        OrganizationsCacheData,
        BaseReferences<
          _$AppDatabase,
          $OrganizationsCacheTable,
          OrganizationsCacheData
        >,
      ),
      OrganizationsCacheData,
      PrefetchHooks Function()
    >;
typedef $$EntitlementsCacheTableCreateCompanionBuilder =
    EntitlementsCacheCompanion Function({
      required int organizationId,
      required String slug,
      required String rawJson,
      Value<int> rowid,
    });
typedef $$EntitlementsCacheTableUpdateCompanionBuilder =
    EntitlementsCacheCompanion Function({
      Value<int> organizationId,
      Value<String> slug,
      Value<String> rawJson,
      Value<int> rowid,
    });

class $$EntitlementsCacheTableFilterComposer
    extends Composer<_$AppDatabase, $EntitlementsCacheTable> {
  $$EntitlementsCacheTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get slug => $composableBuilder(
    column: $table.slug,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$EntitlementsCacheTableOrderingComposer
    extends Composer<_$AppDatabase, $EntitlementsCacheTable> {
  $$EntitlementsCacheTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get slug => $composableBuilder(
    column: $table.slug,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$EntitlementsCacheTableAnnotationComposer
    extends Composer<_$AppDatabase, $EntitlementsCacheTable> {
  $$EntitlementsCacheTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get slug =>
      $composableBuilder(column: $table.slug, builder: (column) => column);

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$EntitlementsCacheTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $EntitlementsCacheTable,
          EntitlementsCacheData,
          $$EntitlementsCacheTableFilterComposer,
          $$EntitlementsCacheTableOrderingComposer,
          $$EntitlementsCacheTableAnnotationComposer,
          $$EntitlementsCacheTableCreateCompanionBuilder,
          $$EntitlementsCacheTableUpdateCompanionBuilder,
          (
            EntitlementsCacheData,
            BaseReferences<
              _$AppDatabase,
              $EntitlementsCacheTable,
              EntitlementsCacheData
            >,
          ),
          EntitlementsCacheData,
          PrefetchHooks Function()
        > {
  $$EntitlementsCacheTableTableManager(
    _$AppDatabase db,
    $EntitlementsCacheTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$EntitlementsCacheTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$EntitlementsCacheTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$EntitlementsCacheTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<String> slug = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => EntitlementsCacheCompanion(
                organizationId: organizationId,
                slug: slug,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required String slug,
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => EntitlementsCacheCompanion.insert(
                organizationId: organizationId,
                slug: slug,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$EntitlementsCacheTable, EntitlementsCacheData>(
                    table,
                  ),
                  BaseReferences<
                    _$AppDatabase,
                    $EntitlementsCacheTable,
                    EntitlementsCacheData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$EntitlementsCacheTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $EntitlementsCacheTable,
      EntitlementsCacheData,
      $$EntitlementsCacheTableFilterComposer,
      $$EntitlementsCacheTableOrderingComposer,
      $$EntitlementsCacheTableAnnotationComposer,
      $$EntitlementsCacheTableCreateCompanionBuilder,
      $$EntitlementsCacheTableUpdateCompanionBuilder,
      (
        EntitlementsCacheData,
        BaseReferences<
          _$AppDatabase,
          $EntitlementsCacheTable,
          EntitlementsCacheData
        >,
      ),
      EntitlementsCacheData,
      PrefetchHooks Function()
    >;
typedef $$CategoriesTableCreateCompanionBuilder = CategoriesCompanion Function({
  required int organizationId,
  required int id,
  Value<int?> shopId,
  required String name,
  Value<String?> status,
  required String rawJson,
  Value<int> rowid,
});
typedef $$CategoriesTableUpdateCompanionBuilder = CategoriesCompanion Function({
  Value<int> organizationId,
  Value<int> id,
  Value<int?> shopId,
  Value<String> name,
  Value<String?> status,
  Value<String> rawJson,
  Value<int> rowid,
});

class $$CategoriesTableFilterComposer
    extends Composer<_$AppDatabase, $CategoriesTable> {
  $$CategoriesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CategoriesTableOrderingComposer
    extends Composer<_$AppDatabase, $CategoriesTable> {
  $$CategoriesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CategoriesTableAnnotationComposer
    extends Composer<_$AppDatabase, $CategoriesTable> {
  $$CategoriesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$CategoriesTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CategoriesTable,
          Category,
          $$CategoriesTableFilterComposer,
          $$CategoriesTableOrderingComposer,
          $$CategoriesTableAnnotationComposer,
          $$CategoriesTableCreateCompanionBuilder,
          $$CategoriesTableUpdateCompanionBuilder,
          (Category, BaseReferences<_$AppDatabase, $CategoriesTable, Category>),
          Category,
          PrefetchHooks Function()
        > {
  $$CategoriesTableTableManager(_$AppDatabase db, $CategoriesTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CategoriesTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CategoriesTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CategoriesTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> id = const Value.absent(),
                Value<int?> shopId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String?> status = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => CategoriesCompanion(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                name: name,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int id,
                Value<int?> shopId = const Value.absent(),
                required String name,
                Value<String?> status = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => CategoriesCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                name: name,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$CategoriesTable, Category>(table),
                  BaseReferences<_$AppDatabase, $CategoriesTable, Category>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CategoriesTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CategoriesTable,
      Category,
      $$CategoriesTableFilterComposer,
      $$CategoriesTableOrderingComposer,
      $$CategoriesTableAnnotationComposer,
      $$CategoriesTableCreateCompanionBuilder,
      $$CategoriesTableUpdateCompanionBuilder,
      (Category, BaseReferences<_$AppDatabase, $CategoriesTable, Category>),
      Category,
      PrefetchHooks Function()
    >;
typedef $$ProductsTableCreateCompanionBuilder = ProductsCompanion Function({
  required int organizationId,
  required int id,
  required int shopId,
  Value<int?> categoryId,
  required String name,
  Value<String?> sku,
  Value<String?> barcode,
  required int salePrice,
  Value<int?> purchasePrice,
  Value<String?> status,
  required String rawJson,
  Value<int> rowid,
});
typedef $$ProductsTableUpdateCompanionBuilder = ProductsCompanion Function({
  Value<int> organizationId,
  Value<int> id,
  Value<int> shopId,
  Value<int?> categoryId,
  Value<String> name,
  Value<String?> sku,
  Value<String?> barcode,
  Value<int> salePrice,
  Value<int?> purchasePrice,
  Value<String?> status,
  Value<String> rawJson,
  Value<int> rowid,
});

class $$ProductsTableFilterComposer
    extends Composer<_$AppDatabase, $ProductsTable> {
  $$ProductsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get categoryId => $composableBuilder(
    column: $table.categoryId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get sku => $composableBuilder(
    column: $table.sku,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get salePrice => $composableBuilder(
    column: $table.salePrice,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$ProductsTableOrderingComposer
    extends Composer<_$AppDatabase, $ProductsTable> {
  $$ProductsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get categoryId => $composableBuilder(
    column: $table.categoryId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get sku => $composableBuilder(
    column: $table.sku,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get salePrice => $composableBuilder(
    column: $table.salePrice,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$ProductsTableAnnotationComposer
    extends Composer<_$AppDatabase, $ProductsTable> {
  $$ProductsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<int> get categoryId => $composableBuilder(
    column: $table.categoryId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get sku =>
      $composableBuilder(column: $table.sku, builder: (column) => column);

  GeneratedColumn<String> get barcode =>
      $composableBuilder(column: $table.barcode, builder: (column) => column);

  GeneratedColumn<int> get salePrice =>
      $composableBuilder(column: $table.salePrice, builder: (column) => column);

  GeneratedColumn<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$ProductsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $ProductsTable,
          Product,
          $$ProductsTableFilterComposer,
          $$ProductsTableOrderingComposer,
          $$ProductsTableAnnotationComposer,
          $$ProductsTableCreateCompanionBuilder,
          $$ProductsTableUpdateCompanionBuilder,
          (Product, BaseReferences<_$AppDatabase, $ProductsTable, Product>),
          Product,
          PrefetchHooks Function()
        > {
  $$ProductsTableTableManager(_$AppDatabase db, $ProductsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ProductsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ProductsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ProductsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> id = const Value.absent(),
                Value<int> shopId = const Value.absent(),
                Value<int?> categoryId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String?> sku = const Value.absent(),
                Value<String?> barcode = const Value.absent(),
                Value<int> salePrice = const Value.absent(),
                Value<int?> purchasePrice = const Value.absent(),
                Value<String?> status = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ProductsCompanion(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                categoryId: categoryId,
                name: name,
                sku: sku,
                barcode: barcode,
                salePrice: salePrice,
                purchasePrice: purchasePrice,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int id,
                required int shopId,
                Value<int?> categoryId = const Value.absent(),
                required String name,
                Value<String?> sku = const Value.absent(),
                Value<String?> barcode = const Value.absent(),
                required int salePrice,
                Value<int?> purchasePrice = const Value.absent(),
                Value<String?> status = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => ProductsCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                categoryId: categoryId,
                name: name,
                sku: sku,
                barcode: barcode,
                salePrice: salePrice,
                purchasePrice: purchasePrice,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$ProductsTable, Product>(table),
                  BaseReferences<_$AppDatabase, $ProductsTable, Product>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$ProductsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $ProductsTable,
      Product,
      $$ProductsTableFilterComposer,
      $$ProductsTableOrderingComposer,
      $$ProductsTableAnnotationComposer,
      $$ProductsTableCreateCompanionBuilder,
      $$ProductsTableUpdateCompanionBuilder,
      (Product, BaseReferences<_$AppDatabase, $ProductsTable, Product>),
      Product,
      PrefetchHooks Function()
    >;
typedef $$ProductVariantsTableCreateCompanionBuilder =
    ProductVariantsCompanion Function({
      required int organizationId,
      required int id,
      required int shopId,
      required int productId,
      required String name,
      Value<String?> sku,
      Value<String?> barcode,
      required int salePrice,
      Value<int?> purchasePrice,
      Value<String?> attributesJson,
      required String rawJson,
      Value<int> rowid,
    });
typedef $$ProductVariantsTableUpdateCompanionBuilder =
    ProductVariantsCompanion Function({
      Value<int> organizationId,
      Value<int> id,
      Value<int> shopId,
      Value<int> productId,
      Value<String> name,
      Value<String?> sku,
      Value<String?> barcode,
      Value<int> salePrice,
      Value<int?> purchasePrice,
      Value<String?> attributesJson,
      Value<String> rawJson,
      Value<int> rowid,
    });

class $$ProductVariantsTableFilterComposer
    extends Composer<_$AppDatabase, $ProductVariantsTable> {
  $$ProductVariantsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get sku => $composableBuilder(
    column: $table.sku,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get salePrice => $composableBuilder(
    column: $table.salePrice,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get attributesJson => $composableBuilder(
    column: $table.attributesJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$ProductVariantsTableOrderingComposer
    extends Composer<_$AppDatabase, $ProductVariantsTable> {
  $$ProductVariantsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get sku => $composableBuilder(
    column: $table.sku,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get salePrice => $composableBuilder(
    column: $table.salePrice,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get attributesJson => $composableBuilder(
    column: $table.attributesJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$ProductVariantsTableAnnotationComposer
    extends Composer<_$AppDatabase, $ProductVariantsTable> {
  $$ProductVariantsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<int> get productId =>
      $composableBuilder(column: $table.productId, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get sku =>
      $composableBuilder(column: $table.sku, builder: (column) => column);

  GeneratedColumn<String> get barcode =>
      $composableBuilder(column: $table.barcode, builder: (column) => column);

  GeneratedColumn<int> get salePrice =>
      $composableBuilder(column: $table.salePrice, builder: (column) => column);

  GeneratedColumn<int> get purchasePrice => $composableBuilder(
    column: $table.purchasePrice,
    builder: (column) => column,
  );

  GeneratedColumn<String> get attributesJson => $composableBuilder(
    column: $table.attributesJson,
    builder: (column) => column,
  );

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$ProductVariantsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $ProductVariantsTable,
          ProductVariant,
          $$ProductVariantsTableFilterComposer,
          $$ProductVariantsTableOrderingComposer,
          $$ProductVariantsTableAnnotationComposer,
          $$ProductVariantsTableCreateCompanionBuilder,
          $$ProductVariantsTableUpdateCompanionBuilder,
          (
            ProductVariant,
            BaseReferences<
              _$AppDatabase,
              $ProductVariantsTable,
              ProductVariant
            >,
          ),
          ProductVariant,
          PrefetchHooks Function()
        > {
  $$ProductVariantsTableTableManager(
    _$AppDatabase db,
    $ProductVariantsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ProductVariantsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ProductVariantsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ProductVariantsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> id = const Value.absent(),
                Value<int> shopId = const Value.absent(),
                Value<int> productId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String?> sku = const Value.absent(),
                Value<String?> barcode = const Value.absent(),
                Value<int> salePrice = const Value.absent(),
                Value<int?> purchasePrice = const Value.absent(),
                Value<String?> attributesJson = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ProductVariantsCompanion(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                productId: productId,
                name: name,
                sku: sku,
                barcode: barcode,
                salePrice: salePrice,
                purchasePrice: purchasePrice,
                attributesJson: attributesJson,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int id,
                required int shopId,
                required int productId,
                required String name,
                Value<String?> sku = const Value.absent(),
                Value<String?> barcode = const Value.absent(),
                required int salePrice,
                Value<int?> purchasePrice = const Value.absent(),
                Value<String?> attributesJson = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => ProductVariantsCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                productId: productId,
                name: name,
                sku: sku,
                barcode: barcode,
                salePrice: salePrice,
                purchasePrice: purchasePrice,
                attributesJson: attributesJson,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$ProductVariantsTable, ProductVariant>(table),
                  BaseReferences<
                    _$AppDatabase,
                    $ProductVariantsTable,
                    ProductVariant
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$ProductVariantsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $ProductVariantsTable,
      ProductVariant,
      $$ProductVariantsTableFilterComposer,
      $$ProductVariantsTableOrderingComposer,
      $$ProductVariantsTableAnnotationComposer,
      $$ProductVariantsTableCreateCompanionBuilder,
      $$ProductVariantsTableUpdateCompanionBuilder,
      (
        ProductVariant,
        BaseReferences<_$AppDatabase, $ProductVariantsTable, ProductVariant>,
      ),
      ProductVariant,
      PrefetchHooks Function()
    >;
typedef $$CustomersTableCreateCompanionBuilder = CustomersCompanion Function({
  required int organizationId,
  required int id,
  Value<int?> shopId,
  required String name,
  Value<String?> phone,
  Value<String?> email,
  Value<String?> status,
  required String rawJson,
  Value<int> rowid,
});
typedef $$CustomersTableUpdateCompanionBuilder = CustomersCompanion Function({
  Value<int> organizationId,
  Value<int> id,
  Value<int?> shopId,
  Value<String> name,
  Value<String?> phone,
  Value<String?> email,
  Value<String?> status,
  Value<String> rawJson,
  Value<int> rowid,
});

class $$CustomersTableFilterComposer
    extends Composer<_$AppDatabase, $CustomersTable> {
  $$CustomersTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get phone => $composableBuilder(
    column: $table.phone,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get email => $composableBuilder(
    column: $table.email,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CustomersTableOrderingComposer
    extends Composer<_$AppDatabase, $CustomersTable> {
  $$CustomersTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get phone => $composableBuilder(
    column: $table.phone,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get email => $composableBuilder(
    column: $table.email,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CustomersTableAnnotationComposer
    extends Composer<_$AppDatabase, $CustomersTable> {
  $$CustomersTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get phone =>
      $composableBuilder(column: $table.phone, builder: (column) => column);

  GeneratedColumn<String> get email =>
      $composableBuilder(column: $table.email, builder: (column) => column);

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$CustomersTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CustomersTable,
          Customer,
          $$CustomersTableFilterComposer,
          $$CustomersTableOrderingComposer,
          $$CustomersTableAnnotationComposer,
          $$CustomersTableCreateCompanionBuilder,
          $$CustomersTableUpdateCompanionBuilder,
          (Customer, BaseReferences<_$AppDatabase, $CustomersTable, Customer>),
          Customer,
          PrefetchHooks Function()
        > {
  $$CustomersTableTableManager(_$AppDatabase db, $CustomersTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CustomersTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CustomersTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CustomersTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> id = const Value.absent(),
                Value<int?> shopId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String?> phone = const Value.absent(),
                Value<String?> email = const Value.absent(),
                Value<String?> status = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => CustomersCompanion(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                name: name,
                phone: phone,
                email: email,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int id,
                Value<int?> shopId = const Value.absent(),
                required String name,
                Value<String?> phone = const Value.absent(),
                Value<String?> email = const Value.absent(),
                Value<String?> status = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => CustomersCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                name: name,
                phone: phone,
                email: email,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$CustomersTable, Customer>(table),
                  BaseReferences<_$AppDatabase, $CustomersTable, Customer>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CustomersTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CustomersTable,
      Customer,
      $$CustomersTableFilterComposer,
      $$CustomersTableOrderingComposer,
      $$CustomersTableAnnotationComposer,
      $$CustomersTableCreateCompanionBuilder,
      $$CustomersTableUpdateCompanionBuilder,
      (Customer, BaseReferences<_$AppDatabase, $CustomersTable, Customer>),
      Customer,
      PrefetchHooks Function()
    >;
typedef $$StockLevelsTableCreateCompanionBuilder =
    StockLevelsCompanion Function({
      required int organizationId,
      required int shopId,
      required int stockLocationId,
      required String stockIdentity,
      Value<int?> productId,
      Value<int?> variantId,
      required double quantity,
      Value<double?> reservedQuantity,
      required String rawJson,
      Value<int> rowid,
    });
typedef $$StockLevelsTableUpdateCompanionBuilder =
    StockLevelsCompanion Function({
      Value<int> organizationId,
      Value<int> shopId,
      Value<int> stockLocationId,
      Value<String> stockIdentity,
      Value<int?> productId,
      Value<int?> variantId,
      Value<double> quantity,
      Value<double?> reservedQuantity,
      Value<String> rawJson,
      Value<int> rowid,
    });

class $$StockLevelsTableFilterComposer
    extends Composer<_$AppDatabase, $StockLevelsTable> {
  $$StockLevelsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get stockLocationId => $composableBuilder(
    column: $table.stockLocationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get stockIdentity => $composableBuilder(
    column: $table.stockIdentity,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get variantId => $composableBuilder(
    column: $table.variantId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get quantity => $composableBuilder(
    column: $table.quantity,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get reservedQuantity => $composableBuilder(
    column: $table.reservedQuantity,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$StockLevelsTableOrderingComposer
    extends Composer<_$AppDatabase, $StockLevelsTable> {
  $$StockLevelsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get stockLocationId => $composableBuilder(
    column: $table.stockLocationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get stockIdentity => $composableBuilder(
    column: $table.stockIdentity,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get variantId => $composableBuilder(
    column: $table.variantId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get quantity => $composableBuilder(
    column: $table.quantity,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get reservedQuantity => $composableBuilder(
    column: $table.reservedQuantity,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$StockLevelsTableAnnotationComposer
    extends Composer<_$AppDatabase, $StockLevelsTable> {
  $$StockLevelsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<int> get stockLocationId => $composableBuilder(
    column: $table.stockLocationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get stockIdentity => $composableBuilder(
    column: $table.stockIdentity,
    builder: (column) => column,
  );

  GeneratedColumn<int> get productId =>
      $composableBuilder(column: $table.productId, builder: (column) => column);

  GeneratedColumn<int> get variantId =>
      $composableBuilder(column: $table.variantId, builder: (column) => column);

  GeneratedColumn<double> get quantity =>
      $composableBuilder(column: $table.quantity, builder: (column) => column);

  GeneratedColumn<double> get reservedQuantity => $composableBuilder(
    column: $table.reservedQuantity,
    builder: (column) => column,
  );

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$StockLevelsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $StockLevelsTable,
          StockLevel,
          $$StockLevelsTableFilterComposer,
          $$StockLevelsTableOrderingComposer,
          $$StockLevelsTableAnnotationComposer,
          $$StockLevelsTableCreateCompanionBuilder,
          $$StockLevelsTableUpdateCompanionBuilder,
          (
            StockLevel,
            BaseReferences<_$AppDatabase, $StockLevelsTable, StockLevel>,
          ),
          StockLevel,
          PrefetchHooks Function()
        > {
  $$StockLevelsTableTableManager(_$AppDatabase db, $StockLevelsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$StockLevelsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$StockLevelsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$StockLevelsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> shopId = const Value.absent(),
                Value<int> stockLocationId = const Value.absent(),
                Value<String> stockIdentity = const Value.absent(),
                Value<int?> productId = const Value.absent(),
                Value<int?> variantId = const Value.absent(),
                Value<double> quantity = const Value.absent(),
                Value<double?> reservedQuantity = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => StockLevelsCompanion(
                organizationId: organizationId,
                shopId: shopId,
                stockLocationId: stockLocationId,
                stockIdentity: stockIdentity,
                productId: productId,
                variantId: variantId,
                quantity: quantity,
                reservedQuantity: reservedQuantity,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int shopId,
                required int stockLocationId,
                required String stockIdentity,
                Value<int?> productId = const Value.absent(),
                Value<int?> variantId = const Value.absent(),
                required double quantity,
                Value<double?> reservedQuantity = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => StockLevelsCompanion.insert(
                organizationId: organizationId,
                shopId: shopId,
                stockLocationId: stockLocationId,
                stockIdentity: stockIdentity,
                productId: productId,
                variantId: variantId,
                quantity: quantity,
                reservedQuantity: reservedQuantity,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$StockLevelsTable, StockLevel>(table),
                  BaseReferences<_$AppDatabase, $StockLevelsTable, StockLevel>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$StockLevelsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $StockLevelsTable,
      StockLevel,
      $$StockLevelsTableFilterComposer,
      $$StockLevelsTableOrderingComposer,
      $$StockLevelsTableAnnotationComposer,
      $$StockLevelsTableCreateCompanionBuilder,
      $$StockLevelsTableUpdateCompanionBuilder,
      (
        StockLevel,
        BaseReferences<_$AppDatabase, $StockLevelsTable, StockLevel>,
      ),
      StockLevel,
      PrefetchHooks Function()
    >;
typedef $$CashSessionsTableCreateCompanionBuilder =
    CashSessionsCompanion Function({
      required int organizationId,
      required int id,
      Value<int?> shopId,
      Value<int?> terminalId,
      Value<String?> status,
      required String rawJson,
      Value<int> rowid,
    });
typedef $$CashSessionsTableUpdateCompanionBuilder =
    CashSessionsCompanion Function({
      Value<int> organizationId,
      Value<int> id,
      Value<int?> shopId,
      Value<int?> terminalId,
      Value<String?> status,
      Value<String> rawJson,
      Value<int> rowid,
    });

class $$CashSessionsTableFilterComposer
    extends Composer<_$AppDatabase, $CashSessionsTable> {
  $$CashSessionsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get terminalId => $composableBuilder(
    column: $table.terminalId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CashSessionsTableOrderingComposer
    extends Composer<_$AppDatabase, $CashSessionsTable> {
  $$CashSessionsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get terminalId => $composableBuilder(
    column: $table.terminalId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get rawJson => $composableBuilder(
    column: $table.rawJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CashSessionsTableAnnotationComposer
    extends Composer<_$AppDatabase, $CashSessionsTable> {
  $$CashSessionsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<int> get terminalId => $composableBuilder(
    column: $table.terminalId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get rawJson =>
      $composableBuilder(column: $table.rawJson, builder: (column) => column);
}

class $$CashSessionsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CashSessionsTable,
          CashSession,
          $$CashSessionsTableFilterComposer,
          $$CashSessionsTableOrderingComposer,
          $$CashSessionsTableAnnotationComposer,
          $$CashSessionsTableCreateCompanionBuilder,
          $$CashSessionsTableUpdateCompanionBuilder,
          (
            CashSession,
            BaseReferences<_$AppDatabase, $CashSessionsTable, CashSession>,
          ),
          CashSession,
          PrefetchHooks Function()
        > {
  $$CashSessionsTableTableManager(_$AppDatabase db, $CashSessionsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CashSessionsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CashSessionsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CashSessionsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> id = const Value.absent(),
                Value<int?> shopId = const Value.absent(),
                Value<int?> terminalId = const Value.absent(),
                Value<String?> status = const Value.absent(),
                Value<String> rawJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => CashSessionsCompanion(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                terminalId: terminalId,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int id,
                Value<int?> shopId = const Value.absent(),
                Value<int?> terminalId = const Value.absent(),
                Value<String?> status = const Value.absent(),
                required String rawJson,
                Value<int> rowid = const Value.absent(),
              }) => CashSessionsCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: shopId,
                terminalId: terminalId,
                status: status,
                rawJson: rawJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$CashSessionsTable, CashSession>(table),
                  BaseReferences<
                    _$AppDatabase,
                    $CashSessionsTable,
                    CashSession
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CashSessionsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CashSessionsTable,
      CashSession,
      $$CashSessionsTableFilterComposer,
      $$CashSessionsTableOrderingComposer,
      $$CashSessionsTableAnnotationComposer,
      $$CashSessionsTableCreateCompanionBuilder,
      $$CashSessionsTableUpdateCompanionBuilder,
      (
        CashSession,
        BaseReferences<_$AppDatabase, $CashSessionsTable, CashSession>,
      ),
      CashSession,
      PrefetchHooks Function()
    >;
typedef $$SyncMetadataTableCreateCompanionBuilder =
    SyncMetadataCompanion Function({
      Value<int> organizationId,
      Value<int?> lastCursor,
      Value<DateTime?> lastSyncAt,
    });
typedef $$SyncMetadataTableUpdateCompanionBuilder =
    SyncMetadataCompanion Function({
      Value<int> organizationId,
      Value<int?> lastCursor,
      Value<DateTime?> lastSyncAt,
    });

class $$SyncMetadataTableFilterComposer
    extends Composer<_$AppDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get lastCursor => $composableBuilder(
    column: $table.lastCursor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get lastSyncAt => $composableBuilder(
    column: $table.lastSyncAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$SyncMetadataTableOrderingComposer
    extends Composer<_$AppDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get lastCursor => $composableBuilder(
    column: $table.lastCursor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get lastSyncAt => $composableBuilder(
    column: $table.lastSyncAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$SyncMetadataTableAnnotationComposer
    extends Composer<_$AppDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get lastCursor => $composableBuilder(
    column: $table.lastCursor,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get lastSyncAt => $composableBuilder(
    column: $table.lastSyncAt,
    builder: (column) => column,
  );
}

class $$SyncMetadataTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $SyncMetadataTable,
          SyncMetadataData,
          $$SyncMetadataTableFilterComposer,
          $$SyncMetadataTableOrderingComposer,
          $$SyncMetadataTableAnnotationComposer,
          $$SyncMetadataTableCreateCompanionBuilder,
          $$SyncMetadataTableUpdateCompanionBuilder,
          (
            SyncMetadataData,
            BaseReferences<_$AppDatabase, $SyncMetadataTable, SyncMetadataData>,
          ),
          SyncMetadataData,
          PrefetchHooks Function()
        > {
  $$SyncMetadataTableTableManager(_$AppDatabase db, $SyncMetadataTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$SyncMetadataTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$SyncMetadataTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$SyncMetadataTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int?> lastCursor = const Value.absent(),
                Value<DateTime?> lastSyncAt = const Value.absent(),
              }) => SyncMetadataCompanion(
                organizationId: organizationId,
                lastCursor: lastCursor,
                lastSyncAt: lastSyncAt,
              ),
          createCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int?> lastCursor = const Value.absent(),
                Value<DateTime?> lastSyncAt = const Value.absent(),
              }) => SyncMetadataCompanion.insert(
                organizationId: organizationId,
                lastCursor: lastCursor,
                lastSyncAt: lastSyncAt,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$SyncMetadataTable, SyncMetadataData>(table),
                  BaseReferences<
                    _$AppDatabase,
                    $SyncMetadataTable,
                    SyncMetadataData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$SyncMetadataTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $SyncMetadataTable,
      SyncMetadataData,
      $$SyncMetadataTableFilterComposer,
      $$SyncMetadataTableOrderingComposer,
      $$SyncMetadataTableAnnotationComposer,
      $$SyncMetadataTableCreateCompanionBuilder,
      $$SyncMetadataTableUpdateCompanionBuilder,
      (
        SyncMetadataData,
        BaseReferences<_$AppDatabase, $SyncMetadataTable, SyncMetadataData>,
      ),
      SyncMetadataData,
      PrefetchHooks Function()
    >;
typedef $$BootstrapMetadataTableCreateCompanionBuilder =
    BootstrapMetadataCompanion Function({
      required int organizationId,
      required int shopId,
      required DateTime lastBootstrapAt,
      Value<int> rowid,
    });
typedef $$BootstrapMetadataTableUpdateCompanionBuilder =
    BootstrapMetadataCompanion Function({
      Value<int> organizationId,
      Value<int> shopId,
      Value<DateTime> lastBootstrapAt,
      Value<int> rowid,
    });

class $$BootstrapMetadataTableFilterComposer
    extends Composer<_$AppDatabase, $BootstrapMetadataTable> {
  $$BootstrapMetadataTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get lastBootstrapAt => $composableBuilder(
    column: $table.lastBootstrapAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$BootstrapMetadataTableOrderingComposer
    extends Composer<_$AppDatabase, $BootstrapMetadataTable> {
  $$BootstrapMetadataTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get lastBootstrapAt => $composableBuilder(
    column: $table.lastBootstrapAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$BootstrapMetadataTableAnnotationComposer
    extends Composer<_$AppDatabase, $BootstrapMetadataTable> {
  $$BootstrapMetadataTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<DateTime> get lastBootstrapAt => $composableBuilder(
    column: $table.lastBootstrapAt,
    builder: (column) => column,
  );
}

class $$BootstrapMetadataTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $BootstrapMetadataTable,
          BootstrapMetadataData,
          $$BootstrapMetadataTableFilterComposer,
          $$BootstrapMetadataTableOrderingComposer,
          $$BootstrapMetadataTableAnnotationComposer,
          $$BootstrapMetadataTableCreateCompanionBuilder,
          $$BootstrapMetadataTableUpdateCompanionBuilder,
          (
            BootstrapMetadataData,
            BaseReferences<
              _$AppDatabase,
              $BootstrapMetadataTable,
              BootstrapMetadataData
            >,
          ),
          BootstrapMetadataData,
          PrefetchHooks Function()
        > {
  $$BootstrapMetadataTableTableManager(
    _$AppDatabase db,
    $BootstrapMetadataTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$BootstrapMetadataTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$BootstrapMetadataTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$BootstrapMetadataTableAnnotationComposer(
                $db: db,
                $table: table,
              ),
          updateCompanionCallback:
              ({
                Value<int> organizationId = const Value.absent(),
                Value<int> shopId = const Value.absent(),
                Value<DateTime> lastBootstrapAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => BootstrapMetadataCompanion(
                organizationId: organizationId,
                shopId: shopId,
                lastBootstrapAt: lastBootstrapAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required int organizationId,
                required int shopId,
                required DateTime lastBootstrapAt,
                Value<int> rowid = const Value.absent(),
              }) => BootstrapMetadataCompanion.insert(
                organizationId: organizationId,
                shopId: shopId,
                lastBootstrapAt: lastBootstrapAt,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$BootstrapMetadataTable, BootstrapMetadataData>(
                    table,
                  ),
                  BaseReferences<
                    _$AppDatabase,
                    $BootstrapMetadataTable,
                    BootstrapMetadataData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$BootstrapMetadataTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $BootstrapMetadataTable,
      BootstrapMetadataData,
      $$BootstrapMetadataTableFilterComposer,
      $$BootstrapMetadataTableOrderingComposer,
      $$BootstrapMetadataTableAnnotationComposer,
      $$BootstrapMetadataTableCreateCompanionBuilder,
      $$BootstrapMetadataTableUpdateCompanionBuilder,
      (
        BootstrapMetadataData,
        BaseReferences<
          _$AppDatabase,
          $BootstrapMetadataTable,
          BootstrapMetadataData
        >,
      ),
      BootstrapMetadataData,
      PrefetchHooks Function()
    >;
typedef $$SyncOutboxTableCreateCompanionBuilder = SyncOutboxCompanion Function({
  Value<int> id,
  required int organizationId,
  required int shopId,
  required int deviceId,
  required String eventUuid,
  required String entityType,
  required String entityId,
  required String action,
  required String payloadJson,
  required DateTime occurredAt,
  Value<String> status,
  Value<int> attemptCount,
  Value<DateTime?> lastAttemptAt,
  Value<String?> lastError,
  Value<String?> serverResultJson,
  Value<String?> failureKind,
  Value<int?> httpStatusCode,
  required DateTime createdAt,
  required DateTime updatedAt,
});
typedef $$SyncOutboxTableUpdateCompanionBuilder = SyncOutboxCompanion Function({
  Value<int> id,
  Value<int> organizationId,
  Value<int> shopId,
  Value<int> deviceId,
  Value<String> eventUuid,
  Value<String> entityType,
  Value<String> entityId,
  Value<String> action,
  Value<String> payloadJson,
  Value<DateTime> occurredAt,
  Value<String> status,
  Value<int> attemptCount,
  Value<DateTime?> lastAttemptAt,
  Value<String?> lastError,
  Value<String?> serverResultJson,
  Value<String?> failureKind,
  Value<int?> httpStatusCode,
  Value<DateTime> createdAt,
  Value<DateTime> updatedAt,
});

class $$SyncOutboxTableFilterComposer
    extends Composer<_$AppDatabase, $SyncOutboxTable> {
  $$SyncOutboxTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get deviceId => $composableBuilder(
    column: $table.deviceId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get eventUuid => $composableBuilder(
    column: $table.eventUuid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get entityType => $composableBuilder(
    column: $table.entityType,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get entityId => $composableBuilder(
    column: $table.entityId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get action => $composableBuilder(
    column: $table.action,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get attemptCount => $composableBuilder(
    column: $table.attemptCount,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get lastAttemptAt => $composableBuilder(
    column: $table.lastAttemptAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get serverResultJson => $composableBuilder(
    column: $table.serverResultJson,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get failureKind => $composableBuilder(
    column: $table.failureKind,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get httpStatusCode => $composableBuilder(
    column: $table.httpStatusCode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get createdAt => $composableBuilder(
    column: $table.createdAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$SyncOutboxTableOrderingComposer
    extends Composer<_$AppDatabase, $SyncOutboxTable> {
  $$SyncOutboxTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get shopId => $composableBuilder(
    column: $table.shopId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get deviceId => $composableBuilder(
    column: $table.deviceId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get eventUuid => $composableBuilder(
    column: $table.eventUuid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get entityType => $composableBuilder(
    column: $table.entityType,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get entityId => $composableBuilder(
    column: $table.entityId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get action => $composableBuilder(
    column: $table.action,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get attemptCount => $composableBuilder(
    column: $table.attemptCount,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get lastAttemptAt => $composableBuilder(
    column: $table.lastAttemptAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get lastError => $composableBuilder(
    column: $table.lastError,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get serverResultJson => $composableBuilder(
    column: $table.serverResultJson,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get failureKind => $composableBuilder(
    column: $table.failureKind,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get httpStatusCode => $composableBuilder(
    column: $table.httpStatusCode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get createdAt => $composableBuilder(
    column: $table.createdAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$SyncOutboxTableAnnotationComposer
    extends Composer<_$AppDatabase, $SyncOutboxTable> {
  $$SyncOutboxTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<int> get organizationId => $composableBuilder(
    column: $table.organizationId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get shopId =>
      $composableBuilder(column: $table.shopId, builder: (column) => column);

  GeneratedColumn<int> get deviceId =>
      $composableBuilder(column: $table.deviceId, builder: (column) => column);

  GeneratedColumn<String> get eventUuid =>
      $composableBuilder(column: $table.eventUuid, builder: (column) => column);

  GeneratedColumn<String> get entityType => $composableBuilder(
    column: $table.entityType,
    builder: (column) => column,
  );

  GeneratedColumn<String> get entityId =>
      $composableBuilder(column: $table.entityId, builder: (column) => column);

  GeneratedColumn<String> get action =>
      $composableBuilder(column: $table.action, builder: (column) => column);

  GeneratedColumn<String> get payloadJson => $composableBuilder(
    column: $table.payloadJson,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<int> get attemptCount => $composableBuilder(
    column: $table.attemptCount,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get lastAttemptAt => $composableBuilder(
    column: $table.lastAttemptAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get lastError =>
      $composableBuilder(column: $table.lastError, builder: (column) => column);

  GeneratedColumn<String> get serverResultJson => $composableBuilder(
    column: $table.serverResultJson,
    builder: (column) => column,
  );

  GeneratedColumn<String> get failureKind => $composableBuilder(
    column: $table.failureKind,
    builder: (column) => column,
  );

  GeneratedColumn<int> get httpStatusCode => $composableBuilder(
    column: $table.httpStatusCode,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get createdAt =>
      $composableBuilder(column: $table.createdAt, builder: (column) => column);

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$SyncOutboxTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $SyncOutboxTable,
          SyncOutboxData,
          $$SyncOutboxTableFilterComposer,
          $$SyncOutboxTableOrderingComposer,
          $$SyncOutboxTableAnnotationComposer,
          $$SyncOutboxTableCreateCompanionBuilder,
          $$SyncOutboxTableUpdateCompanionBuilder,
          (
            SyncOutboxData,
            BaseReferences<_$AppDatabase, $SyncOutboxTable, SyncOutboxData>,
          ),
          SyncOutboxData,
          PrefetchHooks Function()
        > {
  $$SyncOutboxTableTableManager(_$AppDatabase db, $SyncOutboxTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$SyncOutboxTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$SyncOutboxTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$SyncOutboxTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<int> organizationId = const Value.absent(),
                Value<int> shopId = const Value.absent(),
                Value<int> deviceId = const Value.absent(),
                Value<String> eventUuid = const Value.absent(),
                Value<String> entityType = const Value.absent(),
                Value<String> entityId = const Value.absent(),
                Value<String> action = const Value.absent(),
                Value<String> payloadJson = const Value.absent(),
                Value<DateTime> occurredAt = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<int> attemptCount = const Value.absent(),
                Value<DateTime?> lastAttemptAt = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<String?> serverResultJson = const Value.absent(),
                Value<String?> failureKind = const Value.absent(),
                Value<int?> httpStatusCode = const Value.absent(),
                Value<DateTime> createdAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
              }) => SyncOutboxCompanion(
                id: id,
                organizationId: organizationId,
                shopId: shopId,
                deviceId: deviceId,
                eventUuid: eventUuid,
                entityType: entityType,
                entityId: entityId,
                action: action,
                payloadJson: payloadJson,
                occurredAt: occurredAt,
                status: status,
                attemptCount: attemptCount,
                lastAttemptAt: lastAttemptAt,
                lastError: lastError,
                serverResultJson: serverResultJson,
                failureKind: failureKind,
                httpStatusCode: httpStatusCode,
                createdAt: createdAt,
                updatedAt: updatedAt,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required int organizationId,
                required int shopId,
                required int deviceId,
                required String eventUuid,
                required String entityType,
                required String entityId,
                required String action,
                required String payloadJson,
                required DateTime occurredAt,
                Value<String> status = const Value.absent(),
                Value<int> attemptCount = const Value.absent(),
                Value<DateTime?> lastAttemptAt = const Value.absent(),
                Value<String?> lastError = const Value.absent(),
                Value<String?> serverResultJson = const Value.absent(),
                Value<String?> failureKind = const Value.absent(),
                Value<int?> httpStatusCode = const Value.absent(),
                required DateTime createdAt,
                required DateTime updatedAt,
              }) => SyncOutboxCompanion.insert(
                id: id,
                organizationId: organizationId,
                shopId: shopId,
                deviceId: deviceId,
                eventUuid: eventUuid,
                entityType: entityType,
                entityId: entityId,
                action: action,
                payloadJson: payloadJson,
                occurredAt: occurredAt,
                status: status,
                attemptCount: attemptCount,
                lastAttemptAt: lastAttemptAt,
                lastError: lastError,
                serverResultJson: serverResultJson,
                failureKind: failureKind,
                httpStatusCode: httpStatusCode,
                createdAt: createdAt,
                updatedAt: updatedAt,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$SyncOutboxTable, SyncOutboxData>(table),
                  BaseReferences<
                    _$AppDatabase,
                    $SyncOutboxTable,
                    SyncOutboxData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$SyncOutboxTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $SyncOutboxTable,
      SyncOutboxData,
      $$SyncOutboxTableFilterComposer,
      $$SyncOutboxTableOrderingComposer,
      $$SyncOutboxTableAnnotationComposer,
      $$SyncOutboxTableCreateCompanionBuilder,
      $$SyncOutboxTableUpdateCompanionBuilder,
      (
        SyncOutboxData,
        BaseReferences<_$AppDatabase, $SyncOutboxTable, SyncOutboxData>,
      ),
      SyncOutboxData,
      PrefetchHooks Function()
    >;

class $AppDatabaseManager {
  final _$AppDatabase _db;
  $AppDatabaseManager(this._db);
  $$OrganizationsCacheTableTableManager get organizationsCache =>
      $$OrganizationsCacheTableTableManager(_db, _db.organizationsCache);
  $$EntitlementsCacheTableTableManager get entitlementsCache =>
      $$EntitlementsCacheTableTableManager(_db, _db.entitlementsCache);
  $$CategoriesTableTableManager get categories =>
      $$CategoriesTableTableManager(_db, _db.categories);
  $$ProductsTableTableManager get products =>
      $$ProductsTableTableManager(_db, _db.products);
  $$ProductVariantsTableTableManager get productVariants =>
      $$ProductVariantsTableTableManager(_db, _db.productVariants);
  $$CustomersTableTableManager get customers =>
      $$CustomersTableTableManager(_db, _db.customers);
  $$StockLevelsTableTableManager get stockLevels =>
      $$StockLevelsTableTableManager(_db, _db.stockLevels);
  $$CashSessionsTableTableManager get cashSessions =>
      $$CashSessionsTableTableManager(_db, _db.cashSessions);
  $$SyncMetadataTableTableManager get syncMetadata =>
      $$SyncMetadataTableTableManager(_db, _db.syncMetadata);
  $$BootstrapMetadataTableTableManager get bootstrapMetadata =>
      $$BootstrapMetadataTableTableManager(_db, _db.bootstrapMetadata);
  $$SyncOutboxTableTableManager get syncOutbox =>
      $$SyncOutboxTableTableManager(_db, _db.syncOutbox);
}
